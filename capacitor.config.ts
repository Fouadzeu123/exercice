import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.armicm.app',
  appName: 'ARM Holding',
  webDir: 'public',
  server: {
    // URL du serveur de production
    url: 'https://armicm.com',
    androidScheme: 'https',
    iosScheme: 'https',
    // Pour les tests locaux décommentez la ligne ci-dessous :
    // url: 'http://192.168.1.XX:8000',
    // cleartext: true,
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      launchAutoHide: false,
      backgroundColor: '#05020c',
      androidSplashResourceName: 'splash',
      androidScaleType: 'CENTER_CROP',
      showSpinner: false,
      splashFullScreen: true,
      splashImmersive: true,
    },
    StatusBar: {
      style: 'Dark',
      backgroundColor: '#05020c',
    },
  },
  android: {
    allowMixedContent: true,
    backgroundColor: '#05020c',
    buildOptions: {
      releaseType: 'APK',
    },
  },
  ios: {
    backgroundColor: '#05020c',
    contentInset: 'always',
    preferredContentMode: 'mobile',
  },
};

export default config;
