importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyAS4Gb80L-qGj0s9MwE8iX2OkbaMwsSGZM",
    authDomain: "connectx-50b64.firebaseapp.com",
    projectId: "connectx-50b64",
    storageBucket: "connectx-50b64.firebasestorage.app",
    messagingSenderId: "356178114666",
    appId: "1:356178114666:web:16ea31c4fbd3aabe05f213",
    measurementId: "G-ZPTGJW889K"
});

const messaging = firebase.messaging();