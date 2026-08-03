/**
 * Logger utility to prevent console.log in production builds
 * Only logs in development mode (__DEV__)
 */
export const logger = {
  log: (__DEV__ ? console.log : () => {}),
  error: console.error, // Always log errors
  warn: console.warn,   // Always log warnings
  info: console.info,
};

export default logger;
