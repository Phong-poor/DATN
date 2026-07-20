import AsyncStorage from '@react-native-async-storage/async-storage';

/**
 * API Cache Utility
 * Implements in-memory and AsyncStorage caching for API responses
 * Cache duration: 5 minutes (300000ms) for frequently changing data like prices and stock
 */

const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes in milliseconds
const CACHE_KEY_PREFIX = '@api_cache:';

// In-memory cache for fast access
const memoryCache = new Map();

// Pending requests to prevent duplicate API calls
const pendingRequests = new Map();

/**
 * Get cache key for a URL
 */
const getCacheKey = (url) => `${CACHE_KEY_PREFIX}${url}`;

/**
 * Check if cached data is still valid
 */
const isCacheValid = (cacheEntry) => {
  if (!cacheEntry || !cacheEntry.timestamp) return false;
  const now = Date.now();
  return (now - cacheEntry.timestamp) < CACHE_DURATION;
};

/**
 * Get cached data from memory first, then AsyncStorage
 */
export const getCachedData = async (url) => {
  try {
    // Check memory cache first (fastest)
    const memoryCached = memoryCache.get(url);
    if (memoryCached && isCacheValid(memoryCached)) {
      console.log(`[Cache HIT - Memory] ${url}`);
      return memoryCached.data;
    }

    // Check AsyncStorage cache
    const cacheKey = getCacheKey(url);
    const cachedString = await AsyncStorage.getItem(cacheKey);
    
    if (cachedString) {
      const cacheEntry = JSON.parse(cachedString);
      
      if (isCacheValid(cacheEntry)) {
        console.log(`[Cache HIT - Storage] ${url}`);
        // Restore to memory cache for next access
        memoryCache.set(url, cacheEntry);
        return cacheEntry.data;
      } else {
        // Cache expired, remove it
        console.log(`[Cache EXPIRED] ${url}`);
        await AsyncStorage.removeItem(cacheKey);
        memoryCache.delete(url);
      }
    }
    
    console.log(`[Cache MISS] ${url}`);
    return null;
  } catch (error) {
    console.error('[Cache Error] Failed to get cached data:', error);
    return null;
  }
};

/**
 * Save data to cache (both memory and AsyncStorage)
 */
export const setCachedData = async (url, data) => {
  try {
    const cacheEntry = {
      data,
      timestamp: Date.now()
    };

    // Save to memory cache
    memoryCache.set(url, cacheEntry);

    // Save to AsyncStorage for persistence
    const cacheKey = getCacheKey(url);
    await AsyncStorage.setItem(cacheKey, JSON.stringify(cacheEntry));
    
    console.log(`[Cache SET] ${url}`);
  } catch (error) {
    console.error('[Cache Error] Failed to set cached data:', error);
  }
};

/**
 * Clear cache for specific URL or all cache
 */
export const clearCache = async (url = null) => {
  try {
    if (url) {
      // Clear specific URL
      memoryCache.delete(url);
      const cacheKey = getCacheKey(url);
      await AsyncStorage.removeItem(cacheKey);
      console.log(`[Cache CLEAR] ${url}`);
    } else {
      // Clear all cache
      memoryCache.clear();
      const keys = await AsyncStorage.getAllKeys();
      const cacheKeys = keys.filter(key => key.startsWith(CACHE_KEY_PREFIX));
      await AsyncStorage.multiRemove(cacheKeys);
      console.log('[Cache CLEAR ALL]');
    }
  } catch (error) {
    console.error('[Cache Error] Failed to clear cache:', error);
  }
};

/**
 * Fetch with cache and request deduplication
 * Prevents multiple identical requests from running simultaneously
 */
export const fetchWithCache = async (url, fetchFunction) => {
  try {
    // Check cache first
    const cachedData = await getCachedData(url);
    if (cachedData !== null) {
      // Reconstruct Axios-like response object for compatibility
      return { data: cachedData };
    }

    // Check if there's already a pending request for this URL
    const pendingRequest = pendingRequests.get(url);
    if (pendingRequest) {
      console.log(`[Request DEDUPE] Waiting for existing request: ${url}`);
      return await pendingRequest;
    }

    // Create new request
    console.log(`[Request START] ${url}`);
    const requestPromise = fetchFunction()
      .then(async (response) => {
        // Extract only the JSON data part of the response (avoiding config, request etc.)
        const dataToCache = (response && typeof response === 'object' && response.data !== undefined)
          ? response.data
          : response;

        // Cache the clean data
        await setCachedData(url, dataToCache);
        
        // Remove from pending requests
        pendingRequests.delete(url);
        console.log(`[Request SUCCESS] ${url}`);
        return response;
      })
      .catch((error) => {
        // Remove from pending requests on error
        pendingRequests.delete(url);
        console.error(`[Request ERROR] ${url}:`, error);
        throw error;
      });

    // Store pending request
    pendingRequests.set(url, requestPromise);

    return await requestPromise;
  } catch (error) {
    throw error;
  }
};

/**
 * Invalidate cache for URLs matching a pattern
 * Useful when data is updated and cache needs to be refreshed
 */
export const invalidateCachePattern = async (pattern) => {
  try {
    // Clear from memory cache
    const memoryKeys = Array.from(memoryCache.keys());
    memoryKeys.forEach(key => {
      if (key.includes(pattern)) {
        memoryCache.delete(key);
        console.log(`[Cache INVALIDATE - Memory] ${key}`);
      }
    });

    // Clear from AsyncStorage
    const keys = await AsyncStorage.getAllKeys();
    const cacheKeys = keys.filter(key => 
      key.startsWith(CACHE_KEY_PREFIX) && key.includes(pattern)
    );
    await AsyncStorage.multiRemove(cacheKeys);
    console.log(`[Cache INVALIDATE - Storage] Pattern: ${pattern}, Count: ${cacheKeys.length}`);
  } catch (error) {
    console.error('[Cache Error] Failed to invalidate cache pattern:', error);
  }
};

/**
 * Get cache statistics (for debugging)
 */
export const getCacheStats = () => {
  return {
    memoryCacheSize: memoryCache.size,
    pendingRequestsCount: pendingRequests.size,
    cacheDuration: CACHE_DURATION,
    cacheKeys: Array.from(memoryCache.keys())
  };
};
