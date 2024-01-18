#include <SoftwareSerial.h>
#include <ArduinoJson.h>
#include <LiquidCrystal.h>
#include <Wire.h>
#include <SPI.h>
#include <Adafruit_Sensor.h>
#include "Adafruit_BME680.h"

// initialize the library by associating any needed LCD interface pin
// with the arduino pin number it is connected to
const int rs = 12, en = 11, d4 = 5, d5 = 4, d6 = 3, d7 = 2;
LiquidCrystal lcd(rs, en, d4, d5, d6, d7);
SoftwareSerial gauntlet(9, 10);
SoftwareSerial nodemcu(7, 8);

// make degree symbol:
byte degree[8] = {0b00110,0b01001,0b01001,0b00110,0b00000,0b00000,0b00000,0b00000};
byte skull[8] =  {0b00000,0b01110,0b10101,0b11011,0b01110,0b01110,0b00000,0b00000};

#define SEALEVELPRESSURE_HPA (1013.25)
#define NOTE_B0  31
#define NOTE_C1  33
#define NOTE_CS1 35
#define NOTE_D1  37
#define NOTE_DS1 39
#define NOTE_E1  41
#define NOTE_F1  44
#define NOTE_FS1 46
#define NOTE_G1  49
#define NOTE_GS1 52
#define NOTE_A1  55
#define NOTE_AS1 58
#define NOTE_B1  62
#define NOTE_C2  65
#define NOTE_CS2 69
#define NOTE_D2  73
#define NOTE_DS2 78
#define NOTE_E2  82
#define NOTE_F2  87
#define NOTE_FS2 93
#define NOTE_G2  98
#define NOTE_GS2 104
#define NOTE_A2  110
#define NOTE_AS2 117
#define NOTE_B2  123
#define NOTE_C3  131
#define NOTE_CS3 139
#define NOTE_D3  147
#define NOTE_DS3 156
#define NOTE_E3  165
#define NOTE_F3  175
#define NOTE_FS3 185
#define NOTE_G3  196
#define NOTE_GS3 208
#define NOTE_A3  220
#define NOTE_AS3 233
#define NOTE_B3  247
#define NOTE_C4  262
#define NOTE_CS4 277
#define NOTE_D4  294
#define NOTE_DS4 311
#define NOTE_E4  330
#define NOTE_F4  349
#define NOTE_FS4 370
#define NOTE_G4  392
#define NOTE_GS4 415
#define NOTE_A4  440
#define NOTE_AS4 466
#define NOTE_B4  494
#define NOTE_C5  523
#define NOTE_CS5 554
#define NOTE_D5  587
#define NOTE_DS5 622
#define NOTE_E5  659
#define NOTE_F5  698
#define NOTE_FS5 740
#define NOTE_G5  784
#define NOTE_GS5 831
#define NOTE_A5  880
#define NOTE_AS5 932
#define NOTE_B5  988
#define NOTE_C6  1047
#define NOTE_CS6 1109
#define NOTE_D6  1175
#define NOTE_DS6 1245
#define NOTE_E6  1319
#define NOTE_F6  1397
#define NOTE_FS6 1480
#define NOTE_G6  1568
#define NOTE_GS6 1661
#define NOTE_A6  1760
#define NOTE_AS6 1865
#define NOTE_B6  1976
#define NOTE_C7  2093
#define NOTE_CS7 2217
#define NOTE_D7  2349
#define NOTE_DS7 2489
#define NOTE_E7  2637
#define NOTE_F7  2794
#define NOTE_FS7 2960
#define NOTE_G7  3136
#define NOTE_GS7 3322
#define NOTE_A7  3520
#define NOTE_AS7 3729
#define NOTE_B7  3951
#define NOTE_C8  4186
#define NOTE_CS8 4435
#define NOTE_D8  4699
#define NOTE_DS8 4978
#define REST      0


// change this to make the song slower or faster
int tempo = 200;

// change this to whichever pin you want to use


// notes of the moledy followed by the duration.
// a 4 means a quarter note, 8 an eighteenth , 16 sixteenth, so on
// !!negative numbers are used to represent dotted notes,
// so -4 means a dotted quarter note, that is, a quarter plus an eighteenth!!
int melody[] = {

  // Super Mario Bros theme
  // Score available at https://musescore.com/user/2123/scores/2145
  // Theme by Koji Kondo
  

  NOTE_G4,-8, NOTE_E5,-8, NOTE_G5,-8, NOTE_A5,4, NOTE_F5,8, NOTE_G5,8,
  REST,8, NOTE_E5,4,NOTE_C5,8, NOTE_D5,8, NOTE_B4,-4,

};
int notes = sizeof(melody) / sizeof(melody[0]) / 2;

// this calculates the duration of a whole note in ms
int wholenote = (60000 * 4) / tempo;

int divider = 0, noteDuration = 0;
const byte switchPin = A0;
int screen = A3;

Adafruit_BME680 bme; // I2C

const int buzzer = 13;
int ct = 0;
float Ftemp;
float Qair;
float noHum;

void setup() {
  nodemcu.begin(4800);
  delay(1000);
  Serial.println("Program started");
  pinMode(A2, OUTPUT);

  //sets up switch pin as a digital input
  pinMode(switchPin, INPUT_PULLUP);
  pinMode(screen, OUTPUT);

  // set up the LCD's number of columns and rows:
  lcd.begin(16, 2);
  lcd.createChar(0, degree); // create a new degree character
  lcd.createChar(1, skull); // create a new degree character
  
  Serial.begin(4800);
  gauntlet.begin(4800);
//  nodemcu.begin(9600);

  while (!Serial);
  Serial.println(F("BME680 test"));

  if (!bme.begin()) {
    Serial.println("Could not find a valid BME680 sensor, check wiring!");
    while (1);
  }

  // Set up oversampling and filter initialization
  bme.setTemperatureOversampling(BME680_OS_8X);
  bme.setHumidityOversampling(BME680_OS_2X);
  bme.setPressureOversampling(BME680_OS_4X);
  bme.setIIRFilterSize(BME680_FILTER_SIZE_3);
  bme.setGasHeater(320, 150); // 320*C for 150 ms

  //pinMode(buttonPin, INPUT);
  pinMode(buzzer,OUTPUT);
}

void playSong() {
 for (int thisNote = 0; thisNote < notes * 2; thisNote = thisNote + 2) {

    // calculates the duration of each note
    divider = melody[thisNote + 1];
    if (divider > 0) {
      // regular note, just proceed
      noteDuration = (wholenote) / divider;
    } else if (divider < 0) {
      // dotted notes are represented with negative durations!!
      noteDuration = (wholenote) / abs(divider);
      noteDuration *= 1.5; // increases the duration in half for dotted notes
    }

    // we only play the note for 90% of the duration, leaving 10% as a pause
    tone(buzzer, melody[thisNote], noteDuration * 0.9);

    // Wait for the specief duration before playing the next note.
    delay(noteDuration);

    // stop the waveform generation before the next note.
    noTone(buzzer);
  }
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

