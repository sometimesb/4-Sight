#include <WiFi.h>// Include the WiFi library
#include <HTTPClient.h>// Include the HTTPClient library
#include <ArduinoJson.h>// Include the ArduinoJson library
#include <SoftwareSerial.h>// Include the SoftwareSerial library and create a new SoftwareSerial instance for communicating with the NodeMCU
#include <FastCRC.h>

SoftwareSerial nodemcu(22, 23);// Create a new SoftwareSerial instance for communicating with the Gauntlet
SoftwareSerial gauntlet(19, 21);// Create a new SoftwareSerial instance for communicating with the Gauntlet

const char* ssid = ""; // variable to store the WiFi network SSID
const char* password = ""; // variable to store the WiFi network password
const char* orderkey = ""; // variable to store the order key
const int timer = 15000; // variable to store the time interval in milliseconds between sending requests to the server
const int iterationtimer = 1000; // variable to store the time interval in milliseconds between iterations in the main loop

unsigned long lastUpdateTime = 0;  // variable to keep track of last update time
unsigned long lastPrintTime = 0; //variable to keep track of last print time

String uniqueid;
int previousCoordinateCounts[2] = {0,0}; // array to store previous coordinate counts
int previousCountIndex = 0; // index of the last coordinate count added to the array
int coordinateCount = 0; // current number of coordinates in the JSON document
bool flag = true; // flag to indicate if the number of coordinates has changed between two requests
int size; // variable to store the size of the coordinate array (half the number of coordinates)
bool errorarduinoflag = false;

void setup() {
  Serial.begin(4800); // initialize serial communication
  nodemcu.begin(4800); // initialize software serial for nodemcu module
  gauntlet.begin(4800); // initialize software serial for gauntlet module

  WiFi.begin(ssid, password); // start WiFi connection

  int connectionAttempts = 0;

  // keep trying to connect until successfully connected or too many attempts
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000); // wait for 1 second
    connectionAttempts++; // increment connection attempt count
    Serial.println("Connecting..."); // print connecting message to serial monitor
    if (connectionAttempts >= 100) { // if too many attempts, give up and stop execution
      Serial.println("Failed to connect to WiFi. Check your credentials and try again.");
      while(1); // stop program execution
    }
  }
  Serial.println("Connected"); // print connected message to serial monitor
  Serial.println(); // print an empty line to serial monitor
}

void readAndParseData() {
  // Read the data from gauntlet
  String receivedData = gauntlet.readString();
  HTTPClient http;
  
  // Check if the received data is missing the second bracket
  if (receivedData.indexOf("}") == -1) {
    // If missing, add the second bracket to the received data
    receivedData += '}';
  }
  Serial.println(receivedData);

  // Parse the JSON data
  StaticJsonDocument<200> doc;
  DeserializationError error = deserializeJson(doc, receivedData);

  // Check for parsing errors
  if (error) {
    Serial.print("deserializeJson() failed: ");
    Serial.println(error.c_str());
  } else {
    // Print the received data
    String x = doc["X"].as<String>();
    String y = doc["Y"].as<String>();
    String temp = doc["T"].as<String>();
    String hum = doc["H"].as<String>();
    String q = doc["Q"].as<String>();
    
    Serial.print("Received X: ");
    Serial.println(x);
    Serial.print("Received Y: ");
    Serial.println(y);
    Serial.print("Received Temperature: ");
    Serial.println(temp);
    Serial.print("Received Humidity: ");
    Serial.println(hum);
    Serial.print("Received Air Quality: ");
    Serial.println(q);


    String url = "https://4sightmtsu.xyz/api/writeTime.php?x_coord=" + String(x) + "&y_coord=" + String(y) + "&temperature=" + String(temp) + "&humidity=" + String(hum) + "&air_quality=" + String(q) + "&orderkey=" + String(orderkey);
    http.begin(url);
    int httpCode = http.GET();
    Serial.println("Request URL: " + url);

    if (httpCode > 0) {
      Serial.println("HttpCode is 1");
      http.end();
    }

    Serial.println("-----------------------------------------");
  }
}

void getJsonData() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Error: Not connected to WiFi");
    return;
  }

  HTTPClient http;
  String url = "https://4sightMTSU.xyz/api/waypointsV2.php?orderkey=" + String(orderkey);
  http.begin(url);
  int httpCode = http.GET();
  Serial.println("Request URL: " + url);
  
  if (httpCode == HTTP_CODE_OK) {
    Serial.println("HttpCode is 200");
    DynamicJsonDocument doc(32876);

    DeserializationError error = deserializeJson(doc, http.getString());

    if (error) {
      Serial.println("Error: Failed to deserialize JSON data");
      Serial.println(error.c_str());
      return;
    }

    serializeJsonData(doc);
  } else {
    Serial.println("Error: Failed to retrieve JSON data from server");
    Serial.println("Error code: " + String(httpCode));
  }

  http.end();
}

uint32_t calculateCRC32(String str) {
  uint32_t crc32 = FastCRC32().crc32((const uint8_t*) str.c_str(), str.length());
  return crc32;
}

bool checkcoordinates(int previousCoordinateCounts[], bool & flag) {
  int previousCountIndex = (previousCountIndex + 1) % 2;
  if (previousCoordinateCounts[0] != 0 && previousCoordinateCounts[1] != 0) {
    if (previousCoordinateCounts[0] == previousCoordinateCounts[1]) {
      flag = false;
    } 
    else {
      flag = true;
    }
  } 
  else {
    flag = false;
  }

  return flag;
}

void serializeJsonData(DynamicJsonDocument& doc) {
  String serializedData;
  serializeJson(doc, serializedData);
  uint32_t crc32 = calculateCRC32(serializedData);
  
  coordinateCount = doc.size();
  previousCoordinateCounts[previousCountIndex] = crc32;
  previousCountIndex = (previousCountIndex + 1) % 2;

  size = coordinateCount / 2;
  flag = checkcoordinates(previousCoordinateCounts, flag);
  Serial.print("errorflag is: ");
  Serial.println(errorarduinoflag);
  if (flag || previousCoordinateCounts[1] == 0 || errorarduinoflag) {
    Serial.println("SENDING TO ARDUINO");
    errorarduinoflag = false;

    // Create a new JSON document
    DynamicJsonDocument json(64);
    json["data"] = "wait";

    // Serialize the JSON document to a string
    String jsonString;
    serializeJson(json, jsonString);

    // Send the JSON string to the Arduino
    nodemcu.println(jsonString);
    Serial.println(jsonString);

    delay(6000); // Wait for 6 seconds

    // Serialize the original JSON document to a string
    String serializedData;
    serializeJson(doc, serializedData);


    // Send the serialized data to the Arduino
    nodemcu.println(serializedData);
    Serial.println(serializedData);
    Serial.println("------------------------");
    Serial.println();
  } else {
    Serial.println("NOT SENDING TO ARDUINO");
    Serial.println("------------------------");
    Serial.println();
  }

//  Serial.println("Number of coordinates: " + String(coordinateCount));
//  Serial.println();
}

void loop() {
  // Get new data immediately in the first iteration
  if (lastUpdateTime == 0) {
    lastUpdateTime = millis(); // update last update time
    getJsonData(); // get new data
  }

  // Check if it's time to update
  if (millis() - lastUpdateTime >= timer) { // 30 seconds have passed
    lastUpdateTime = millis();  // update last update time
    getJsonData();  // get new data
  }

  // Check if data is available
  if (nodemcu.available()) {
    String receivedData = nodemcu.readStringUntil('\n');
    if (receivedData.indexOf("error") != -1) {
      Serial.println("Received error message from Arduino!");
      errorarduinoflag = true;
    }
  }
  if (gauntlet.available()) {
    readAndParseData();
  }
   else {
    // Calculate time left until next update
    unsigned long timeLeft = timer - (millis() - lastUpdateTime);

    // Print time left every interation second
    if (millis() - lastPrintTime >= iterationtimer) {
      lastPrintTime = millis();
      Serial.print("Time left until update: ");
      Serial.print(timeLeft);
      Serial.println(" milliseconds");
    }
  }
}
