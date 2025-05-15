// Firebase initialization script
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.7.3/firebase-app.js";
import { getAnalytics } from "https://www.gstatic.com/firebasejs/11.7.3/firebase-analytics.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/11.7.3/firebase-auth.js";

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyBlEYgqEmxapexLxkZEHDvxxajpTpgPrfA",
  authDomain: "tinyurl-ac3ce.firebaseapp.com",
  projectId: "tinyurl-ac3ce",
  storageBucket: "tinyurl-ac3ce.firebasestorage.app",
  messagingSenderId: "447727236633",
  appId: "1:447727236633:web:2e6a14c56545dcd5fb1c88",
  measurementId: "G-9V706YWY0H"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);
const auth = getAuth(app);

export { app, analytics, auth };