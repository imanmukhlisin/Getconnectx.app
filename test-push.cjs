const admin = require("firebase-admin");
const serviceAccount = require("./storage/app/firebase-credentials.json");

console.log("Mulai inisialisasi...");

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const registrationToken = 'f3r50M6X2xDtwSMmPbyJES:APA91bG9DQi1YjSWkfKl6gLgYtNzVZ6AzY0mAo-DT0C4NGmBCzTJCuX12cREDXNJRYLLVWzZT2FkwIu85l7FpDgU0Erm3prAQNdzGFRmp1buNTeRgMlZS70';

const message = {
  notification: {
    title: 'Woi Dimas!',
    body: 'Tes dari Backend nih!'
  },
  token: registrationToken
};

console.log("Sedang mengirim ke Firebase...");

admin.messaging().send(message)
  .then((response) => {
    console.log('BERHASIL! Response:', response);
  })
  .catch((error) => {
    console.error('ERROR KIRIM:', error);
  });