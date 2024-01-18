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


SR04 sonic[4]= {SR04(ECHO_PIN1,TRIG_PIN1),SR04(ECHO_PIN2,TRIG_PIN2),SR04(ECHO_PIN3,TRIG_PIN3),SR04(ECHO_PIN4,TRIG_PIN4)};
float a[4];
float intensity[4];
float minrange=0;
float maxrange=50;
//int motor[]={2,3,4,5,6,7,8,9};


void setup() {
  Serial.begin(9600);
  pwm.begin();
  //pwm.setOscillatorFrequency(27000000);
  pwm.setPWMFreq(1600);//60
  //Wire.setClock(400000);
  //yield();
  pinMode(switchPin, INPUT_PULLUP);
 
}

void loop() {
  byte switchState = LOW;//digitalRead(switchPin);  //read the state of the input pin (HIGH or LOW)
  if (switchState == LOW)  //if it is LOW then the switch is closed
  {
    for(int i=0; i<4; i++){
     a[i]=sonic[i].Distance();
     if(a[i]>=minrange && a[i]<=maxrange){
       intensity[i]=(1-((a[i]-minrange)/maxrange))*(4095);
       //analogWrite(motor[i], inteffgnsity[i]); originally
       pwm.setPWM(i,0,intensity[i]); 
      Serial.print(i);
      Serial.print(' ');
      Serial.println(intensity[i]);
       
     }
      else
      {
        pwm.setPWM(i,0,0); 
      }
    }
  
  } 
  else
  {
    for(int i=0; i<4; i++){
      pwm.setPWM(i,0,0); 
    }
    Serial.println("You weren't suppose to find this message");
  }  
  
}
