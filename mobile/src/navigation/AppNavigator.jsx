import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';

// Import Screens
import HomeScreen from '../screens/HomeScreen';
import CategoryScreen from '../screens/CategoryScreen';
import WishlistScreen from '../screens/WishlistScreen';
import CartScreen from '../screens/CartScreen';
import AccountScreen from '../screens/AccountScreen';
import ProductDetailScreen from '../screens/ProductDetailScreen';
import CheckoutScreen from '../screens/CheckoutScreen';
import OrderSuccessScreen from '../screens/OrderSuccessScreen';
import OrderDetailScreen from '../screens/OrderDetailScreen';
import OrderHistoryScreen from '../screens/OrderHistoryScreen';
import NewsDetailScreen from '../screens/NewsDetailScreen';
import NewsListScreen from '../screens/NewsListScreen';
import ChatbotScreen from '../screens/ChatbotScreen';
import PromotionScreen from '../screens/PromotionScreen';
import AddressScreen from '../screens/AddressScreen';
import ContactScreen from '../screens/ContactScreen';
import LuckyWheelScreen from '../screens/LuckyWheelScreen';
import AffiliateScreen from '../screens/AffiliateScreen';

const Stack = createNativeStackNavigator();

export default function AppNavigator() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      {/* Both name "Main" and "Trang chủ" map to HomeScreen to maintain complete compatibility */}
      <Stack.Screen name="Main" component={HomeScreen} />
      <Stack.Screen name="Trang chủ" component={HomeScreen} />
      <Stack.Screen name="Danh mục" component={CategoryScreen} />
      <Stack.Screen name="Yêu thích" component={WishlistScreen} />
      <Stack.Screen name="Giỏ hàng" component={CartScreen} />
      <Stack.Screen name="Tài khoản" component={AccountScreen} />
      
      <Stack.Screen name="ProductDetail" component={ProductDetailScreen} />
      <Stack.Screen name="Checkout" component={CheckoutScreen} />
      <Stack.Screen name="OrderSuccess" component={OrderSuccessScreen} />
      <Stack.Screen name="OrderDetail" component={OrderDetailScreen} />
      <Stack.Screen name="OrderHistory" component={OrderHistoryScreen} />
      <Stack.Screen name="NewsDetail" component={NewsDetailScreen} />
      <Stack.Screen name="NewsList" component={NewsListScreen} />
      <Stack.Screen name="Chatbot" component={ChatbotScreen} />
      <Stack.Screen name="Promotion" component={PromotionScreen} />
      <Stack.Screen name="Address" component={AddressScreen} />
      <Stack.Screen name="Contact" component={ContactScreen} />
      <Stack.Screen name="LuckyWheel" component={LuckyWheelScreen} />
      <Stack.Screen name="Affiliate" component={AffiliateScreen} />
    </Stack.Navigator>
  );
}
