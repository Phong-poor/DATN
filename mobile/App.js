import React, { useEffect } from 'react';
import { AppState } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { NavigationContainer } from '@react-navigation/native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import AppNavigator from './src/navigation/AppNavigator';
import ErrorBoundary from './src/components/ErrorBoundary';
import Toast from './src/components/Toast';
import { navigationRef } from './src/navigation/navigationService';
import api from './src/services/api';
import useAuthStore from './src/store/useAuthStore';

export default function App() {
  useEffect(() => {
    let timer;
    const sendHeartbeat = () => {
      if (useAuthStore.getState().token) api.post('/user/heartbeat').catch(() => {});
    };
    const start = () => {
      clearInterval(timer);
      sendHeartbeat();
      timer = setInterval(sendHeartbeat, 60000);
    };
    start();
    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active') start();
      else clearInterval(timer);
    });
    return () => {
      clearInterval(timer);
      subscription.remove();
    };
  }, []);

  return (
    <ErrorBoundary>
      <SafeAreaProvider>
        <NavigationContainer ref={navigationRef}>
          <AppNavigator />
          <Toast />
          <StatusBar style="light" />
        </NavigationContainer>
      </SafeAreaProvider>
    </ErrorBoundary>
  );
}
