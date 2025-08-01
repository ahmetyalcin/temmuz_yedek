/*
Template Name: Adminox - Responsive Bootstrap 4 Admin Dashboard
Author: CoderThemes
Version: 2.0.0
Website: https://coderthemes.com/
Contact: support@coderthemes.com
File: Coming Soon init js
*/

class Countdown {
    initCountDown() {
      var m, i;
      document.getElementById("days") &&
        ((m = new Date("Jan 17, 2026 12:00:01").getTime()),
        (i = setInterval(function () {
          var e = new Date().getTime(),
            e = m - e,
            n = Math.floor(e / 864e5),
            t = Math.floor((e % 864e5) / 36e5),
            o = Math.floor((e % 36e5) / 6e4),
            d = Math.floor((e % 6e4) / 1e3);
          (document.getElementById("days").innerHTML = n),
            (document.getElementById("hours").innerHTML = t),
            (document.getElementById("minutes").innerHTML = o),
            (document.getElementById("seconds").innerHTML = d),
            e < 0 &&
              (clearInterval(i),
              (document.getElementById("days").innerHTML = ""),
              (document.getElementById("hours").innerHTML = ""),
              (document.getElementById("minutes").innerHTML = ""),
              (document.getElementById("seconds").innerHTML = ""),
              (document.getElementById("end").innerHTML = "00:00:00:00"));
        }, 1e3)));
    }
    init() {
      this.initCountDown();
    }
  }
  new Countdown().init();