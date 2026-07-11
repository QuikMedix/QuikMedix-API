<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Happy Holidays</title>
<link href="https://fonts.cdnfonts.com/css/kringle" rel="stylesheet">
<style>

body {
    overflow: hidden;
    background-color: #98dbff;
}
    * {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

canvas {
  display: block;
}

#santa-container {
  width: 300px;
  height: 300px;
  position: absolute;
  bottom: 15px;
  left: 0;
}
@media (max-width: 700px) {
  img {
    width: 350px;
  }
  #santa-container {
    transform: scale(0.7);
    bottom: -30px;
    left: -50px;
  }
}

#hat {
  width: 70%;
  position: absolute;
  top: 1.5%;
  left: 50%;
  transform: translate(-50%, 0%);
  z-index: 500;
}
#hat-fur {
  width: 90%;
  height: 45px;
  background: linear-gradient(to bottom, #ffffff 10%, #cccee5 100%);
  border-radius: 10px;
  position: relative;
  z-index: 400;
  margin: 0 auto;
}
#hat-cap {
  width: 68%;
  height: 70px;
  background: linear-gradient(to top right, #960001 10%, #e60027 100%);
  border-top-left-radius: 100px;
  margin: 0 auto;
  position: relative;
  z-index: 100;
}
#hat-pointy {
  width: 12%;
  height: 48%;
  background: linear-gradient(to right, #960001 0%, #e60027 100%);
  position: absolute;
  right: 8%;
  top: 0;
  border-top-right-radius: 20px;
  z-index: 80;
  transform-origin: top left;
  animation: topi 2s linear infinite;
}
#hat-ball {
  width: 65px;
  height: 65px;
  background: radial-gradient(#cccee5 10%, #ffffff 100%);
  position: absolute;
  right: -4%;
  top: 40%;
  border-radius: 100%;
  z-index: 85;
  transform-origin: top center;
  animation: topi 2s linear infinite;
}
#santa-face {
  width: 40%;
  height: 50px;
  background-color: #f2a879;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -60%);
  z-index: 600;
}
#face {
  width: 100%;
  height: 100%;
  background-color: #f2a879;
  position: relative;
  z-index: 800;
}

.eye {
  width: 10px;
  height: 10px;
  border-radius: 100%;
  background: black;
  position: absolute;
  top: 34%;
  animation: aankhe 2.5s linear infinite;
}
.eye.eye--left {
  left: 30%;
}
.eye.eye--right {
  right: 30%;
}

.eyebrow {
  width: 32px;
  height: 20px;
  border-radius: 10px;
  position: absolute;
  top: -5%;
  z-index: 500;
  background: linear-gradient(to bottom, #ffffff 50%, #cccee5 100%);
}
.eyebrow.eyebrow--left {
  left: 20%;
  transform: translate(0, -40%);
  border-bottom-left-radius: 0;
  animation: aankhMareLeft 3s 0.3s ease-in alternate infinite;
}
.eyebrow.eyebrow--right {
  right: 20%;
  border-bottom-right-radius: 0;
  animation: aankhMareRight 3s 0.3s ease-in alternate infinite;
}

@keyframes aankhMareLeft {
  0%,
  100% {
    transform: rotate(0)translate(0, -40%);
  }
  16%,
  80% {
    transform: rotate(-5deg)translate(0, -60%);
  }
}

@keyframes aankhMareRight {
  0%,
  100% {
    transform: rotate(0)translate(0, -40%);
  }
  16%,
  80% {
    transform: rotate(5deg)translate(0, -60%);
  }
}
#nose {
  width: 40px;
  height: 40px;
  background: radial-gradient(#ee9a6c 50%, #ec7e5d 100%);
  border-radius: 100%;
  position: absolute;
  top: 35%;
  left: 50%;
  transform: translate(-50%, 0%);
  z-index: 1000;
  box-shadow: 0 2px 2px rgba(0, 0, 0, 0.3);
}

#mustache {
  width: 140px;
  font-size: 0;
  position: absolute;
  top: 70%;
  left: 50%;
  transform: translate(-50%, 0%);
  z-index: 800;
}

.mustache {
  width: 50%;
  height: 35px;
  background-color: #fff;
  display: inline-block;
  box-shadow: 0 1px 10px 0 rgba(0, 0, 0, 0.3);
}
.mustache.mustache--left {
  border-top-left-radius: 60px;
  border-bottom-right-radius: 50px;
}
.mustache.mustache--right {
  border-top-right-radius: 60px;
  border-bottom-left-radius: 50px;
}
#mouth {
  width: 55px;
  height: 55px;
  background-color: #222;
  border-radius: 100%;
  position: absolute;
  bottom: -70%;
  left: 50%;
  transform: translate(-50%, 0%);
  z-index: 700;
  overflow: hidden;
}

#teeth {
  width: 50px;
  height: 30px;
  background-color: #fff;
  position: absolute;
  top: 7%;
  left: 50%;
  transform: translate(-50%, 0);
}
#tongue {
  width: 32px;
  height: 20px;
  background: linear-gradient(to right, #960001 0%, #e60027 100%);
  position: absolute;
  bottom: -8%;
  left: 0%;
  border-top-right-radius: 15px;
  animation: tongue 1s linear infinite;
}

#beard {
  width: 125%;
  display: inline-block;
  position: absolute;
  bottom: -340%;
  left: 50%;
  transform: translate(-50%, 0%);
  z-index: 600;
}

.beard {
  width: 20%;
  height: 120px;
  float: left;
  border-radius: 40px;
  position: relative;
  box-shadow: 0 12px 20px 0 rgba(0, 0, 0, 0.3);
}

#beard .beard:nth-child(1) {
  transform: scaleX(1.2)translate(0%, -60%);
  z-index: 600;
  background: linear-gradient(to right, #ffffff 10%, #cccee5 100%);
}
#beard .beard:nth-child(2) {
  transform: scaleX(1.2)translate(0%, -45%);
  z-index: 700;
  background: linear-gradient(to right, #ffffff 10%, #cccee5 100%);
}
#beard .beard:nth-child(3) {
  transform: scaleX(1.2)translate(0%, -30%);
  z-index: 800;
  background-color: #fff;
}
#beard .beard:nth-child(4) {
  transform: scaleX(1.2)translate(0%, -45%);
  z-index: 700;
  background: linear-gradient(to left, #ffffff 10%, #cccee5 100%);
}
#beard .beard:nth-child(5) {
  transform: scaleX(1.2)translate(0%, -60%);
  z-index: 600;
  background: linear-gradient(to left, #ffffff 10%, #cccee5 100%);
}
#santa-body {
  width: 320px;
  height: 300px;
  border-radius: 100%;
  background: radial-gradient(#960001 30%, #e60027 100%);
  position: absolute;
  bottom: -55%;
  left: 50%;
  transform: translate(-50%, 0);
}

@keyframes aankhe {
  0%,
  10% {
    transform: scaleY(1);
  }
  5% {
    transform: scaleY(0);
  }
}

@keyframes topi {
  0%,
  50%,
  100% {
    transform: rotate(0deg);
  }
  25% {
    transform: rotate(5deg);
  }
  75% {
    transform: rotate(-5deg);
  }
}
p {
    position: absolute;
    font-family: 'Kringle', sans-serif;
    color: #503f9b;
    font-size: 70px;
    line-height: 55px;
    top: 350px;
    left: 350px;
  }
.back  {
    position: absolute;
    top: 0;
    left: 0;
}

</style>
<script>
    var font;
var particles = [];
var count = false;
var xPos = 300;
var bgColor = "#98dbff";
var t1 = "Merry Christmas";
var t2 = "And Happy New Year. ";
var t1YPos = 190;
var t2YPos = 290;
var particleSize = [2, 25];

function preload() {
  font = loadFont(
    "https://fonts.cdnfonts.com/s/11980/kr.woff"
  );
}

function setup() {
  createCanvas(windowWidth, windowHeight);
  background(bgColor);
  textFont(font);

  if (windowWidth < 700) {
    textSize(40);
    t1YPos = 190;
    t2YPos = 300;

    particleSize = [6, 16];
  } else {
    textSize(100);
  }
  var space = windowWidth - textWidth(t2);
  xPos = space * 0.45;
  console.log({ space, xPos });
  colorText();

  var points = font.textToPoints(t1, xPos, t1YPos);
  points.forEach(p => {
    const particle = new Particle(p.x, p.y);
    particles.push(particle);
  });

  points = font.textToPoints(t2, xPos, t2YPos);
  points.forEach(p => {
    const particle = new Particle(p.x, p.y);
    particles.push(particle);
  });

}

function draw() {
  background(bgColor);
  colorText();
  particles.forEach(p => {
    p.behaviours(colorText);
    p.update();
    p.show();
  });
}

function colorText() {
  noStroke();
  fill("#e41c0d");
  text(t1, xPos, t1YPos);
  fill("#2e9c32");
  text(t2, xPos, t2YPos);
  fill("#2e9c66");
}

function Particle(x, y) {
  this.pos = createVector(width / 2, height);
  this.target = createVector(x, y);
  this.vel = createVector();
  this.acc = createVector();
  this.r = random(particleSize[0], particleSize[1]);
  this.maxSpeed = 10;
  this.maxForce = 1;
}

Particle.prototype.update = function() {
  this.pos.add(this.vel);
  this.vel.add(this.acc);
  this.acc.mult(0);
};

Particle.prototype.show = function() {
  stroke("#fff");
  strokeWeight(this.r);
  point(this.pos.x, this.pos.y);
};

Particle.prototype.behaviours = function() {
  var arrive = this.arrive(this.target);
  var mouse = createVector(mouseX, mouseY);
  var flee = this.flee(mouse);

  arrive.mult(1);
  flee.mult(8);

  if (this.pos.y < 450) {
    this.applyForce(arrive);
    this.applyForce(flee);
  } else {
    this.applyForce(createVector(0, -1));
  }
};

Particle.prototype.applyForce = function(f) {
  this.acc.add(f);
};

Particle.prototype.arrive = function(target) {
  var desired = p5.Vector.sub(target, this.pos);

  var d = desired.mag();
  var speed = this.maxSpeed;
  if (d < 100) {
    speed = map(d, 0, 100, 0, this.maxSpeed);
  }
  desired.setMag(speed);
  var steer = p5.Vector.sub(desired, this.vel);
  steer.limit(this.maxForce);
  return steer;
};

Particle.prototype.flee = function(target) {
  var desired = p5.Vector.sub(target, this.pos);
  var d = desired.mag();
  if (d < 80) {
    desired.setMag(this.maxSpeed);
    desired.mult(-1);
    var steer = p5.Vector.sub(desired, this.vel);
    steer.limit(this.maxForce);
    return steer;
  } else {
    return createVector(0, 0);
  }
};

</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/p5.js/0.7.2/p5.min.js"></script>

</head>

<body >
    <div id="santa-container">
      <div id="hat">
        <div id="hat-cap"></div>
        <div id="hat-pointy"></div>
        <div id="hat-ball"></div>
        <div id="hat-fur"></div>
      </div>
      <div id="santa-face">
        <div id="face">
          <div class="eyebrow eyebrow--left"></div>
          <div class="eyebrow eyebrow--right"></div>
          <div class="eye eye--left"></div>
          <div class="eye eye--right"></div>
    
        </div>
    
        <div id="beard">
          <div class="beard"></div>
          <div class="beard"></div>
          <div class="beard"></div>
          <div class="beard"></div>
          <div class="beard"></div>
        </div>
        <div id="nose"></div>
        <div id="mustache">
          <div class="mustache mustache--left"></div>
          <div class="mustache mustache--right"></div>
        </div>
        <div id="mouth">
          <div id="teeth"></div>
          <div id="tongue"></div>
        </div>
      </div>
      <div id="santa-body"></div>
    </div>
    <a href="https://cp.a2brx.com/"><img src="https://cp.a2brx.com/images/back-3.gif" alt="back"  class="back"></a>
  <p>We would like to take this opportunity to thank you for doing business with us and to wish you a very Merry Christmas and a Happy New Year!</p>


</body>
</html>