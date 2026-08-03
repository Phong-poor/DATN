import { Platform } from 'react-native';

const stripTrailingSlashes = (value) => value?.trim().replace(/\/+$/, '');

const defaultHost = Platform.OS === 'android' ? '10.0.2.2' : '127.0.0.1';
const defaultOrigin = `http://${defaultHost}:8000`;

export const SERVER_ORIGIN = stripTrailingSlashes(
  process.env.EXPO_PUBLIC_SERVER_ORIGIN
) || defaultOrigin;

export const API_BASE_URL = `${SERVER_ORIGIN}/api`;
export const MEDIA_BASE_URL = SERVER_ORIGIN;
