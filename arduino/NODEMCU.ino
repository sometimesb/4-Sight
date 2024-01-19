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

    // Construct the URL for writing data to the server
    String url = "https://4sightmtsu.xyz/api/writeTime.php?x_coord=" + String(x) + "&y_coord=" + String(y) + "&temperature=" + String(temp) + "&humidity=" + String(hum) + "&air_quality=" + String(q) + "&orderkey=" + String(orderkey);
    http.begin(url);

    // Perform a GET request to write data to the server
    int httpCode = http.GET();
    Serial.println("Request URL: " + url);

    // Check if the HTTP response code is greater than 0
    if (httpCode > 0) {
      Serial.println("HttpCode is 1");
      http.end();
    }

    Serial.println("-----------------------------------------");
  }
}

void getJsonData() {
  // Check if connected to WiFi
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Error: Not connected to WiFi");
    return;
  }

  // Create an HTTP client instance
  HTTPClient http;
  
  // Construct the URL for the API endpoint
  String url = "https://4sightMTSU.xyz/api/waypointsV2.php?orderkey=" + String(orderkey);
  
  // Begin the HTTP request
  http.begin(url);
  
  // Perform a GET request and get the HTTP response code
  int httpCode = http.GET();
  Serial.println("Request URL: " + url);
  
  // Check if the HTTP response code is OK (200)
  if (httpCode == HTTP_CODE_OK) {
    Serial.println("HttpCode is 200");

    // Create a JSON document to store the received data
    DynamicJsonDocument doc(32876);

    // Deserialize the JSON data received from the server
    DeserializationError error = deserializeJson(doc, http.getString());

    // Check for deserialization errors
    if (error) {
      Serial.println("Error: Failed to deserialize JSON data");
      Serial.println(error.c_str());
      return;
    }

    // Call the function to serialize and send the JSON data
    serializeJsonData(doc);
  } else {
    Serial.println("Error: Failed to retrieve JSON data from server");
    Serial.println("Error code: " + String(httpCode));
  }

  // End the HTTP connection
  http.end();
}

uint32_t calculateCRC32(String str) {
  // Calculate CRC32 checksum using FastCRC32 library
  uint32_t crc32 = FastCRC32().crc32((const uint8_t*) str.c_str(), str.length());
  
  // Return the calculated CRC32 checksum
  return crc32;
}

bool checkcoordinates(int previousCoordinateCounts[], bool & flag) {
  // Update previousCountIndex for cyclic array access
  int previousCountIndex = (previousCountIndex + 1) % 2;

  // Check if both previous coordinate counts are not zero
  if (previousCoordinateCounts[0] != 0 && previousCoordinateCounts[1] != 0) {
    // Check if the previous counts are equal
    if (previousCoordinateCounts[0] == previousCoordinateCounts[1]) {
      flag = false; // Coordinates are consistent
    } else {
      flag = true;  // Coordinates are inconsistent
    }
  } else {
    flag = false; // At least one of the previous counts is zero
  }

  // Return the flag indicating coordinate consistency
  return flag;
}

void serializeJsonData(DynamicJsonDocument& doc) {
  // Serialize the JSON document to a string
  String serializedData;
  serializeJson(doc, serializedData);

  // Calculate CRC32 checksum for the serialized data
  uint32_t crc32 = calculateCRC32(serializedData);

  // Update coordinate count and previous CRC32 values
  coordinateCount = doc.size();
  previousCoordinateCounts[previousCountIndex] = crc32;
  previousCountIndex = (previousCountIndex + 1) % 2;

  // Calculate the size and check coordinates for consistency
  size = coordinateCount / 2;
  flag = checkcoordinates(previousCoordinateCounts, flag);

  // Print error flag status
  Serial.print("errorflag is: ");
  Serial.println(errorarduinoflag);

  // Check conditions for sending data to Arduino
  if (flag || previousCoordinateCounts[1] == 0 || errorarduinoflag) {
    Serial.println("SENDING TO ARDUINO");
    errorarduinoflag = false; // Reset error flag

    // Create a new JSON document for signaling "wait"
    DynamicJsonDocument json(64);
    json["data"] = "wait";

    // Serialize the "wait" JSON document to a string
    String jsonString;
    serializeJson(json, jsonString);

    // Send the "wait" JSON string to the Arduino
    nodemcu.println(jsonString);
    Serial.println(jsonString);

    delay(6000); // Wait for 6 seconds before sending actual data

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

  // Uncomment the following lines for debugging purposes
  // Serial.println("Number of coordinates: " + String(coordinateCount));
  // Serial.println();
}

void loop() {
  // Play a song (if required)
  // playSong();

  // Read the state of the switch
  byte switchState = digitalRead(switchPin);
  
  // Turn on an external LED
  analogWrite(A2, 255);

  // Display sensor readings on LCD
  lcd.setCursor(0, 0);
  if (!bme.performReading()) {
    Serial.println("Failed to perform reading :(");
    return;
  }

  // Temperature
  Serial.print("Temperature = ");
  Serial.print(bme.temperature - 2.5);
  Serial.print(" *C = ");
  Ftemp = ((bme.temperature - 2.5) * 1.8 + 32);
  Serial.print(Ftemp);
  Serial.println(" *F");

  lcd.print("T=");
  lcd.print(round(Ftemp));
  lcd.write(byte(0));
  lcd.print("F");

  // Pressure
  Serial.print("Pressure = ");
  Serial.print(bme.pressure / 100.0);
  Serial.println(" hPa");

  // Air Quality
  Serial.print("Air:");
  Qair = (bme.gas_resistance / 1000.0);
  Serial.print(Qair);
  Serial.print("KOhms ");

  lcd.setCursor(8, 0);
  lcd.print("Air:");
  if (Qair > 300) {
    lcd.print("Good");
    Serial.println("(Good)");
  } else if (Qair < 120) {
    lcd.print("Poor");
    Serial.println("(Poor)");
  } else {
    lcd.print("Fair");
    Serial.println("(Fair)");
  }

  // Humidity
  Serial.print("Humidity = ");
  noHum = bme.humidity;
  Serial.print(noHum);
  Serial.println(" %");

  lcd.setCursor(0, 1);
  lcd.print("Humidity= ");
  lcd.print(noHum);
  lcd.print("%");

  ct = ct + 1;
  Serial.println(ct);
  Serial.println();

  // Handle switch state
  if (switchState == LOW) {
    // Turn the screen on
    digitalWrite(screen, HIGH);

    // Perform actions based on conditions
    if ((ct > 1300) && (Qair < 20) || (Ftemp > 100)) {
      digitalWrite(buzzer, HIGH);
    }

    if ((ct > 1300) && (Qair < 70)) {
      digitalWrite(buzzer, HIGH);
      delay(100);
      digitalWrite(buzzer, LOW);
      delay(100);
    }

    if ((ct > 1300) && (Qair < 120) || (Ftemp > 90)) {
      digitalWrite(buzzer, HIGH);
      delay(250);
      digitalWrite(buzzer, LOW);
      delay(250);
    }

    if (Qair > 120) {
      digitalWrite(buzzer, LOW);
    }

    Serial.println("switch turned on");
  } else {
    // Turn the screen off
    Serial.println("switch turned off");
    digitalWrite(screen, LOW);
  }

  delay(500);
  Serial.println(" ");

  // Receive coordinates from Gauntlet
  if (gauntlet.available()) {
    String receivedData = gauntlet.readString();
    double X_Cord, Y_Cord;
    bool route_status = false;

    DynamicJsonDocument doc(128);
    deserializeJson(doc, receivedData);

    for (JsonPair pair : doc.as<JsonObject>()) {
      String key = pair.key().c_str();
      String coordinate = pair.value().as<String>();

      if (key == "X_Cord") {
        X_Cord = coordinate.toDouble();
      } else if (key == "Y_Cord") {
        Y_Cord = coordinate.toDouble();
      } else if (key == "route_status") {
        route_status = true;
        if (route_status) {
          Serial.println("RECEIVED ROUTE STATUS!!");
          Serial.println("ready to play song");
          playSong();
        }
      }
    }

    // Clear the JSON document
    doc.clear();

    // Populate JSON document with sensor data
    doc["X"] = X_Cord;
    doc["Y"] = Y_Cord;
    doc["T"] = Ftemp;
    doc["H"] = noHum;
    doc["Q"] = Qair;

    // Print JSON to Serial monitor
    serializeJson(doc, Serial);

    // Send data to NodeMCU
    serializeJson(doc, nodemcu);
    nodemcu.println();
    Serial.println();
    delay(2000);
  }
}

