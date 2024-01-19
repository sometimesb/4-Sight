#include <SR04.h>
#include <Wire.h>
#include <Adafruit_PWMServoDriver.h>

Adafruit_PWMServoDriver pwm = Adafruit_PWMServoDriver();

// Top Right side
#define ECHO_PIN1 10 
#define TRIG_PIN1 13 

//top left side
#define ECHO_PIN2 6
#define TRIG_PIN2 7

//lower right side
#define ECHO_PIN3 8
#define TRIG_PIN3 9

//lower left
#define ECHO_PIN4 2
#define TRIG_PIN4 3

const byte switchPin = A5;

// Create an array of ultrasonic sensors (SR04) with corresponding echo and trigger pins
SR04 sonic[4] = {SR04(ECHO_PIN1, TRIG_PIN1), SR04(ECHO_PIN2, TRIG_PIN2), SR04(ECHO_PIN3, TRIG_PIN3), SR04(ECHO_PIN4, TRIG_PIN4)};

// Arrays to store distance measurements and motor intensities for each ultrasonic sensor
float a[4];
float intensity[4];

// Define minimum and maximum range values for the sensors
float minrange = 0;
float maxrange = 50;

// Initialize the setup function
void setup() {
  // Start serial communication at 9600 baud rate
  Serial.begin(9600);

  // Initialize the Adafruit PWM servo driver
  pwm.begin();

  // Set the PWM frequency to 1600 Hz
  pwm.setPWMFreq(1600);

  // Set the switch pin as INPUT_PULLUP to enable internal pull-up resistor
  pinMode(switchPin, INPUT_PULLUP);
}

void loop() {
  // Read the state of the switch (currently set to LOW for demonstration)
  byte switchState = LOW; // digitalRead(switchPin); 

  // Check if the switch is closed (LOW)
  if (switchState == LOW) {
    // Loop through each ultrasonic sensor
    for (int i = 0; i < 4; i++) {
      // Measure distance using the ultrasonic sensor
      a[i] = sonic[i].Distance();

      // Check if the measured distance is within the specified range
      if (a[i] >= minrange && a[i] <= maxrange) {
        // Calculate intensity based on distance within the range
        intensity[i] = (1 - ((a[i] - minrange) / maxrange)) * 4095;

        // Set PWM signal for the corresponding motor using Adafruit PWM servo driver
        pwm.setPWM(i, 0, intensity[i]);

        // Print sensor index and intensity for debugging
        Serial.print(i);
        Serial.print(' ');
        Serial.println(intensity[i]);
      } else {
        // If distance is outside the range, turn off the corresponding motor
        pwm.setPWM(i, 0, 0);
      }
    }
  } else {
    // If the switch is not closed, turn off all motors
    for (int i = 0; i < 4; i++) {
      pwm.setPWM(i, 0, 0);
    }
    Serial.println("You weren't supposed to find this message");
  }
}

