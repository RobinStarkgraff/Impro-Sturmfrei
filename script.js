// Cross-fades the group photo in the "Wer sind wir?" section.

const PHOTOS = [
  "images/group/1.jpg",
  "images/group/2.jpg",
  "images/group/3.jpg",
  "images/group/4.jpg",
  "images/group/5.jpg"
];

const FADE_MS = 1400; // must stay <= the opacity transition in style.css
const HOLD_MS = 6000;

const photo = document.getElementById("group-photo");

if (photo) {

  let current = 0;

  setInterval(() => {

    photo.style.opacity = 0;

    setTimeout(() => {

      current = (current + 1) % PHOTOS.length;

      photo.onload = () => {
        photo.style.opacity = 1;
      };

      photo.src = PHOTOS[current];

    }, FADE_MS);

  }, HOLD_MS);

}
