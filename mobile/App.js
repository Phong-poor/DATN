import React from 'react';
import { StatusBar } from 'expo-status-bar';
import { NavigationContainer } from '@react-navigation/native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import AppNavigator from './src/navigation/AppNavigator';
import ErrorBoundary from './src/components/ErrorBoundary';
import Toast from './src/components/Toast';
import { navigationRef } from './src/navigation/navigationService';

export default function App() {
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

