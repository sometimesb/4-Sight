#include <SoftwareSerial.h>
#include <ArduinoJson.h>
#include <SR04.h>
#include <TinyGPSPlus.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_BNO055.h>
#include <SparkFun_Ublox_Arduino_Library.h>
#include <Arduino.h>
#include <avr/wdt.h> // Include the ATmel library

SoftwareSerial nodemcu(10, 11);
SoftwareSerial gauntlet(5,6);
SFE_UBLOX_GPS myGPS;
Adafruit_BNO055 bno = Adafruit_BNO055(55, 0x28);

String * latitudes, * longitudes;
double * glatitudes, * glongitudes;

int size;
int spot = 0;
int coordinateCount = 0;
int previousCountIndex = 0;
int previousCoordinateCounts[2] = {0,0};

float latitude;
float longitude;

String errorMessage;

const int pi = M_PI;
const int tolerance = 8.5;
const int motorint = 255;
const int refreshtime = 500;
const int haptic[]={A0, A1, A2, A3,A4,A5,A6,A7}; //has to be PWM

const float gpsconvert = 1 * pow(10, -7);

bool flag = false;
bool endroute = false;
bool coordinateflag = false;
bool waitingForData = false;
bool nodemcuError = false;

const size_t MAX_JSON_SIZE = 3000;
StaticJsonDocument<MAX_JSON_SIZE> doc;

void setup() {
  Serial.begin(4800);
  nodemcu.begin(4800);
  gauntlet.begin(4800);
  
  Serial.println("We just started back up");

  for (int i = 0; i < 8; i++) {
    pinMode(haptic[i], OUTPUT);
  }
  
  while (!bno.begin()) {
    delay(100);
    Serial.println("bno not started");
  }

  while (myGPS.begin() == false) {
    delay(100);
    Serial.println("gps not started");
  }
  uint8_t system, gyro, accel, mag;
  system = gyro = accel = mag = 0;
  myGPS.setI2COutput(COM_TYPE_UBX);
  myGPS.saveConfiguration();
  pinMode(LED_BUILTIN, OUTPUT); // initialize the built-in LED pin as an output

}

void printArrays(String * latitudes, String * longitudes, int size) {
  for (int i = 0; i < size; i++) {
    glatitudes[i] = latitudes[i].toDouble() * gpsconvert;
    glongitudes[i] = longitudes[i].toDouble() * gpsconvert;
  }
}

float head(){
  sensors_event_t orientationData;
  bno.getEvent(&orientationData, Adafruit_BNO055::VECTOR_EULER);
  float head=orientationData.orientation.x;
  return head;
}

void vibrate(float hdif) {
  if(endroute == false){
    int motorvalue = round(hdif / 45);
    if (motorvalue > 7) motorvalue = 0;
    analogWrite(haptic[motorvalue], motorint);
    for (int i = 0; i < 8; i++) {
      if (i != motorvalue)
        analogWrite(haptic[i], 0);
    }
    Serial.print("MOTOR VIBRATED: ");
    Serial.print(motorvalue);
    Serial.println(" ");
  }
  else{
    for (int i = 0; i < 8; i++) {
      analogWrite(haptic[i], 0);
    }
  }
}

void motoroff(int y) {
  for (int i = 0; i < 8; i++) {
    if (i != y) {
      analogWrite(haptic[i], 0);
    }
  }
}

void allmotoroff() {
  for (int i = 0; i < 8; i++) {
    analogWrite(haptic[i], 0);
  }
}


void motoron() {
  for (int i = 0; i < 8; i++) {
    analogWrite(haptic[i], motorint);
  }
}

void receivedata() {
  Serial.println("RECEIVING DATA...");
  String receivedData = nodemcu.readString();
  Serial.println("Received data: " + receivedData);
  DeserializationError error = deserializeJson(doc, receivedData);
  
  if (error) {
    Serial.print("Deserialization error: ");
    Serial.println(error.c_str());
    errorMessage = error.c_str();
    nodemcuError = true;
    return "error";
  }

  if (doc.containsKey("error")) {
    doc.clear();
    return "error";
  }

  if (doc.containsKey("data")) {
    doc.clear();
    Serial.println("Data received, waiting for coordinates...");
    waitingForData = true;
    coordinateflag = false;
    return;
  } else {
    nodemcuError = false;
    delete[] latitudes;
    delete[] longitudes;
    delete[] glatitudes;
    delete[] glongitudes;

    waitingForData = false; // reset the flag
    coordinateflag = true;
    int coordinateCount = 0;

    for (JsonPair pair : doc.as<JsonObject>()) {
      String key = pair.key().c_str();
      String coordinate = pair.value().as<String>();
      coordinateCount++;
//      Serial.println("Coordinate " + key + ": " + coordinate);
    }

    Serial.println("Number of coordinates: " + String(coordinateCount));
    if (coordinateCount == 0) {
      Serial.println("No coordinates received.");
      nodemcuError = true;
      return "error";
    }

    if (coordinateCount % 2 != 0) {
      Serial.println("Received an odd number of coordinates");
      nodemcuError = true;
      return "error";
    }

    size = coordinateCount / 2;
    latitudes = new String[size];
    longitudes = new String[size];
    glatitudes = new double[size];
    glongitudes = new double[size];
    int latIndex = 0;
    int lonIndex = 0;

    for (JsonPair pair : doc.as<JsonObject>()) {
      String key = pair.key().c_str();
      String coordinate = pair.value().as<String>();
      if (key.toInt() % 2 == 0) {
        longitudes[lonIndex] = coordinate;
        lonIndex++;
      } else {
        latitudes[latIndex] = coordinate;
        latIndex++;
      }
    }

    printArrays(latitudes, longitudes, size);
    Serial.print("Size is: ");
    Serial.println(size);
  }
}


void loop() {
  if (nodemcu.available()) {
    endroute=false;
    spot =0;
    receivedata();
  }
  
  if (nodemcuError) {
    nodemcu.print("error");
    nodemcuError = false;
    delay(1000);
    // Reset the microcontroller using the watchdog timer
    wdt_enable(WDTO_15MS); // enable the watchdog timer with a 15ms timeout
    while (1) {} // wait for the microcontroller to reset
    return;
  }

  if (size > 0 && coordinateflag && !endroute) {
    // calculate latitude and longitude
//    float latitude = 35.847452810734;
//    float longitude = -86.366947463602;

    latitude = (myGPS.getLatitude()) * gpsconvert;
    longitude = (myGPS.getLongitude()) * gpsconvert;
    
    // calculate heading and goal heading
    float heading = head();
    float gheading = TinyGPSPlus::courseTo(latitude, longitude, glatitudes[spot], glongitudes[spot]);
    
    // calculate distance and heading difference
    float distance = TinyGPSPlus::distanceBetween(latitude, longitude, glatitudes[spot], glongitudes[spot]);
    float hdif = abs(gheading - heading);
    vibrate(hdif);
    
    // update spot if distance is less than tolerance
    if (distance < tolerance) {
      spot++;
    }
//    if (spot > size*2 ) {
//      Serial.println("OVERFLOW!!");
//      Serial.println(coordinateCount);
//      Serial.println(spot);
//      while(1); // halt all operation and break out of the void loop
//      spot = 0;
//    }

    // print debug information
    Serial.println("------------------------------------");
//    Serial.print("LAT: ");  Serial.print(latitude, 7); Serial.println(" ");
//    Serial.print("LON: ");  Serial.print(longitude, 7); Serial.println(" ");
//    Serial.print("GLAT: ");  Serial.print(glatitudes[spot], 7); Serial.println(" ");
//    Serial.print("GLON: ");  Serial.print(glongitudes[spot], 7); Serial.println(" ");
    Serial.print("HEADING: ");  Serial.print(heading); Serial.println(" ");
    Serial.print("GOAL HEADING: ");  Serial.print(gheading); Serial.println(" ");
//    Serial.print("HEADING DIFFERENCE: ");  Serial.print(hdif); Serial.println(" ");
    Serial.print("SIZE: ");  Serial.print(size); Serial.println(" ");
    Serial.print("DISTANCE TO: ");  Serial.print(distance); Serial.println(" ");
    Serial.print("I value is: "); Serial.print(spot); Serial.println(" ");
    Serial.println("------------------------------------");

    // serialize and send data every 30 seconds
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
    digitalWrite(LED_BUILTIN, HIGH); // turn on the LED
    delay(500);
    if(spot == size+1){
      Serial.println("We hit the end route");
      allmotoroff();
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
    digitalWrite(LED_BUILTIN, LOW); // turn off the LED
  }
}
