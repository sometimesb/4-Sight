#include <SoftwareSerial.h>
#include <ArduinoJson.h>
#include <SR04.h>
#include <TinyGPSPlus.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_BNO055.h>
#include <SparkFun_Ublox_Arduino_Library.h>
#include <Arduino.h>
#include <avr/wdt.h>

// Declaration of SoftwareSerial instances for communication with NodeMCU and Gauntlet
SoftwareSerial nodemcu(10, 11);
SoftwareSerial gauntlet(5, 6);

// Object for handling UBLOX GPS module
SFE_UBLOX_GPS myGPS;

// Object for interfacing with BNO055 sensor
Adafruit_BNO055 bno = Adafruit_BNO055(55, 0x28);

// Arrays to store latitude and longitude strings and their corresponding doubles
String* latitudes;
String* longitudes;
double* glatitudes;
double* glongitudes;

// Variables to manage coordinate data and navigation state
int size;
int spot = 0;
int coordinateCount = 0;
int previousCountIndex = 0;
int previousCoordinateCounts[2] = {0, 0};

// Variables for storing current latitude and longitude
float latitude;
float longitude;

// Variable to store error messages during data processing
String errorMessage;

// Constants for mathematical and system configurations
const int pi = M_PI;
const int tolerance = 8.5;
const int motorint = 255;
const int refreshtime = 500;

// Array of pins for haptic feedback motors (PWM pins)
const int haptic[] = {A0, A1, A2, A3, A4, A5, A6, A7};

// Conversion factor for GPS coordinates
const float gpsconvert = 1 * pow(10, -7);

// Flags and state variables for program control
bool flag = false;
bool endroute = false;
bool coordinateflag = false;
bool waitingForData = false;
bool nodemcuError = false;

// Maximum size for JSON document during serialization
const size_t MAX_JSON_SIZE = 3000;
StaticJsonDocument<MAX_JSON_SIZE> doc;

void setup() {
  // Initialize serial communication with a baud rate of 4800
  Serial.begin(4800);
  nodemcu.begin(4800);
  gauntlet.begin(4800);
  
  // Print a message indicating the start of the program
  Serial.println("We just started back up");

  // Set the mode of haptic feedback pins to OUTPUT
  for (int i = 0; i < 8; i++) {
    pinMode(haptic[i], OUTPUT);
  }
  
  // Initialize the BNO055 sensor
  while (!bno.begin()) {
    delay(100);
    Serial.println("bno not started");
  }

  // Initialize the GPS module
  while (myGPS.begin() == false) {
    delay(100);
    Serial.println("gps not started");
  }

  // Configure GPS module settings
  uint8_t system, gyro, accel, mag;
  system = gyro = accel = mag = 0;
  myGPS.setI2COutput(COM_TYPE_UBX);
  myGPS.saveConfiguration();

  // Initialize the built-in LED pin as an output
  pinMode(LED_BUILTIN, OUTPUT);
}

void printArrays(String * latitudes, String * longitudes, int size) {
  // Convert latitude and longitude Strings to double arrays
  for (int i = 0; i < size; i++) {
    glatitudes[i] = latitudes[i].toDouble() * gpsconvert;
    glongitudes[i] = longitudes[i].toDouble() * gpsconvert;
  }
}

float head() {
  // Get the current heading from the BNO055 sensor
  sensors_event_t orientationData;
  bno.getEvent(&orientationData, Adafruit_BNO055::VECTOR_EULER);
  
  // Extract and return the x-axis orientation (heading)
  float head = orientationData.orientation.x;
  return head;
}

void vibrate(float hdif) {
  // Check if the route has not reached its end
  if (endroute == false) {
    // Calculate motor value based on heading difference
    int motorvalue = round(hdif / 45);
    
    // Ensure motor value does not exceed the available motors
    if (motorvalue > 7) motorvalue = 0;

    // Activate the specified motor and deactivate others
    analogWrite(haptic[motorvalue], motorint);
    for (int i = 0; i < 8; i++) {
      if (i != motorvalue)
        analogWrite(haptic[i], 0);
    }

    // Print a message indicating which motor vibrated
    Serial.print("MOTOR VIBRATED: ");
    Serial.print(motorvalue);
    Serial.println(" ");
  } else {
    // Turn off all motors when the route reaches its end
    for (int i = 0; i < 8; i++) {
      analogWrite(haptic[i], 0);
    }
  }
}

void motoroff(int y) {
  // Turn off all motors except the one specified by 'y'
  for (int i = 0; i < 8; i++) {
    if (i != y) {
      analogWrite(haptic[i], 0); // Set PWM value to 0 for motor deactivation
    }
  }
}

void allmotoroff() {
  // Turn off all motors by setting PWM values to 0
  for (int i = 0; i < 8; i++) {
    analogWrite(haptic[i], 0); // Set PWM value to 0 for motor deactivation
  }
}

void motoron() {
  // Activate all motors by setting PWM values to maximum intensity
  for (int i = 0; i < 8; i++) {
    analogWrite(haptic[i], motorint);
  }
}

void receivedata() {
  // Print a message indicating data reception
  Serial.println("RECEIVING DATA...");
  
  // Read the data received from NodeMCU
  String receivedData = nodemcu.readString();
  
  // Print the received data
  Serial.println("Received data: " + receivedData);
  
  // Attempt to deserialize the received JSON data
  DeserializationError error = deserializeJson(doc, receivedData);
  
  // Check for deserialization errors
  if (error) {
    Serial.print("Deserialization error: ");
    Serial.println(error.c_str());
    errorMessage = error.c_str();
    nodemcuError = true;
    return "error";
  }

  // Check if the JSON data contains an "error" key
  if (doc.containsKey("error")) {
    doc.clear();
    return "error";
  }

  // Check if the JSON data contains a "data" key
  if (doc.containsKey("data")) {
    doc.clear();
    Serial.println("Data received, waiting for coordinates...");
    waitingForData = true;
    coordinateflag = false;
    return;
  } else {
    // Reset error flags and clear memory for previous data
    nodemcuError = false;
    delete[] latitudes;
    delete[] longitudes;
    delete[] glatitudes;
    delete[] glongitudes;

    // Reset flags and counters
    waitingForData = false;
    coordinateflag = true;
    int coordinateCount = 0;

    // Iterate over key-value pairs in the JSON data
    for (JsonPair pair : doc.as<JsonObject>()) {
      String key = pair.key().c_str();
      String coordinate = pair.value().as<String>();
      coordinateCount++;
      // Uncomment the following line to print each coordinate
      // Serial.println("Coordinate " + key + ": " + coordinate);
    }

    // Print the total number of coordinates received
    Serial.println("Number of coordinates: " + String(coordinateCount));

    // Check for invalid number of coordinates
    if (coordinateCount == 0 || coordinateCount % 2 != 0) {
      Serial.println("Invalid number of coordinates received.");
      nodemcuError = true;
      return "error";
    }

    // Calculate the size based on the number of coordinates
    size = coordinateCount / 2;
    
    // Allocate memory for latitude, longitude arrays
    latitudes = new String[size];
    longitudes = new String[size];
    glatitudes = new double[size];
    glongitudes = new double[size];
    
    // Initialize index variables
    int latIndex = 0;
    int lonIndex = 0;

    // Populate latitude and longitude arrays from JSON data
    for (JsonPair pair : doc.as<JsonObject>()) {
      String key = pair.key().c_str();
      String coordinate = pair.value().as<String>();
      
      // Determine whether the coordinate is latitude or longitude
      if (key.toInt() % 2 == 0) {
        longitudes[lonIndex] = coordinate;
        lonIndex++;
      } else {
        latitudes[latIndex] = coordinate;
        latIndex++;
      }
    }

    // Convert String arrays to double arrays
    printArrays(latitudes, longitudes, size);
    
    // Print the size of the arrays
    Serial.print("Size is: ");
    Serial.println(size);
  }
}

void loop() {
  // Check if there is data available from NodeMCU
  if (nodemcu.available()) {
    endroute = false;
    spot = 0;
    receivedata();  // Call the function to process received data
  }
  
  // Check for NodeMCU communication error
  if (nodemcuError) {
    nodemcu.print("error");
    nodemcuError = false;
    delay(1000);

    // Reset the microcontroller using the watchdog timer
    wdt_enable(WDTO_15MS); // Enable the watchdog timer with a 15ms timeout
    while (1) {} // Wait for the microcontroller to reset
    return;
  }

  // Process GPS data if conditions are met
  if (size > 0 && coordinateflag && !endroute) {
    // Calculate latitude and longitude
    latitude = (myGPS.getLatitude()) * gpsconvert;
    longitude = (myGPS.getLongitude()) * gpsconvert;
    
    // Calculate heading and goal heading
    float heading = head();
    float gheading = TinyGPSPlus::courseTo(latitude, longitude, glatitudes[spot], glongitudes[spot]);
    
    // Calculate distance and heading difference
    float distance = TinyGPSPlus::distanceBetween(latitude, longitude, glatitudes[spot], glongitudes[spot]);
    float hdif = abs(gheading - heading);
    vibrate(hdif);
    
    // Update spot if distance is less than tolerance
    if (distance < tolerance) {
      spot++;
    }

    // Print debug information
    Serial.println("------------------------------------");
    Serial.print("HEADING: ");  Serial.print(heading); Serial.println(" ");
    Serial.print("GOAL HEADING: ");  Serial.print(gheading); Serial.println(" ");
    Serial.print("SIZE: ");  Serial.print(size); Serial.println(" ");
    Serial.print("DISTANCE TO: ");  Serial.print(distance); Serial.println(" ");
    Serial.print("I value is: "); Serial.print(spot); Serial.println(" ");
    Serial.println("------------------------------------");

    // Serialize and send data every 30 seconds
    static unsigned long last_sent = 0;
    if (millis() - last_sent >= 30000) {
      DynamicJsonDocument doc(256);
      doc["X_Cord"] = latitude;
      doc["Y_Cord"] = longitude;
      String serialized_data;
      serializeJson(doc, serialized_data);
      Serial.println(serialized_data);
      gauntlet.print(serialized_data);
      last_sent = millis();
    }
    digitalWrite(LED_BUILTIN, HIGH); // Turn on the LED
    delay(500);

    // Check if the end of the route is reached
    if (spot == size + 1) {
      Serial.println("We hit the end route");
      allmotoroff();  // Turn off all motors
      Serial.println("All motors shut off");
      DynamicJsonDocument doc(256);
      doc["route_status"] = true;
      String serialized_data;
      serializeJson(doc, serialized_data);
      Serial.println(serialized_data);
      gauntlet.print(serialized_data);
      endroute = true;
    }
  } else {
    digitalWrite(LED_BUILTIN, LOW); // Turn off the LED
  }
}

