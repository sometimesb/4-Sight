# 🌐 PROJECT 4-Sight
A jacket designed for the blind to get around via strategically located vibrations on their chest and back. 

## 🎥 Introduction
[![Everything Is AWESOME](https://i.imgur.com/rdf8Pne.png)](https://www.youtube.com/watch?v=HHBsU_pVup8)

## 🔧 Technologies Used
NOT a super simple project like everything else, this project took about a year in software + hardware development. 
Only the website code is published above, the arduino (hardware) code is not published.
* HTML - structure
* CSS - styling
* PHP - API
* JS - interaction
* Arduino - hardware control
* MySQL - storing records of each user

## 🚀 Images
![Screenshot of project](https://i.imgur.com/s8Qq1iV.png)
![Screenshot of project](https://i.imgur.com/Y0zJBDL.png)
![Screenshot of project](https://i.imgur.com/sDPQvkn.png)
![Screenshot of project](https://i.imgur.com/mnt8G6H.png)

## 🖥️ System Architecture
It all starts with the server and website! This is a _closed loop system_ if you have any controls backround. Information flows in one direction and we use the feedback in the NODEMCU to gauge what we want to do in the system. 
<p align="center">
  <img width="auto" height="auto" src="https://i.imgur.com/Kz3DKnK.jpg">
</p>

### Flow of Data:

1. User selects a new route on the website. NO route is also a route. This was done to make sure the entire system resets from the old walking route and to leave it in a 'waiting' mode. I enforced a maximum byte size as there is limited RAM on a microcontroller, so I optimize the coordinates before sending.
2. NodeMCU checks website for a route. Check if the checksum of the route is the same as the current running route. We don't want to keep restarting the same route, only start a new route when the checksum is unique.
3. Send waypoint coordinates via json to the GPS Arduino.
4. Arduino GPS begins routing system (and hence vibrational motors), while also powering the independent obstacle detection system.
5. Arduino GPS system sends current location of user (X & Y coordinates) to the Arduino Gauntlet system.
6. Arduino Gauntlet system calculates temperature, humidity, air quality, and passes along the current location to the NodeMCU.
7. NodeMCU passes along all information back to the server, where this information is plotted for each user.

The data provided in this image is purely simulated data!
![Screenshot of project](https://i.imgur.com/KJ4HrAu.png)

### How does routing work?
Great question GitHub header! Now to answer your wonderful question :) The GPS system takes this routing matrix and goes one by one, checking against the users current location. It calculates the desired heading using the built in magnetometer and then uses a 'ring' around the user of vibrational motors. To go north, the front would vibrate, east the side, west the other side, and south the back would vibrate. Therefore, the entire route can look like this:
![image](https://github.com/sometimesb/4-Sight/assets/77695101/a38246fd-4f57-4232-bf68-a1571266e91f)
<p align="center">
  <img width="auto" height="auto" src="https://github.com/sometimesb/4-Sight/assets/77695101/4cb325cc-3494-4acc-9bc7-0f01fcadbc80">
</p>

### How can we make this project better?
Unfortunately, we were at the mercy of budget, the bane of all evil! Just kidding. Some great improvements to this project would be a much better GPS, this had a tolerance of +-1m, which is not good considering that the coordinates can be less then a meter apart. A good GPS would elevate this project to new heights. Another change would be the data source of the routing. We used a free version of google maps API so the routing was not the best, but was usable.

## 📄 Full explanation:
Kindly check out the full run down, includes everything you could have to ask :)

[SD2 Final Report Project 4-Sight.docx](https://github.com/sometimesB/4-Sight/files/13895367/SD2.Final.Report.Project.4-Sight.docx)
