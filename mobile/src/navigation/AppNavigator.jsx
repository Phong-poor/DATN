import React from 'react';
import { StyleSheet, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';

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
import NotificationsScreen from '../screens/NotificationsScreen';
import SupportChatScreen from '../screens/SupportChatScreen';
import { COLORS } from '../utils/theme';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

const TAB_ICONS = {
  'Trang chủ': ['home', 'home-outline'],
  'Danh mục': ['grid', 'grid-outline'],
  'Tài khoản': ['person', 'person-outline'],
};

function MainTabs({ navigation }) {
  return (
    <View style={styles.mainTabsContainer}>
      <Tab.Navigator
        initialRouteName="Trang chủ"
        backBehavior="history"
        screenOptions={({ route }) => ({
          headerShown: false,
          tabBarHideOnKeyboard: true,
          tabBarActiveTintColor: COLORS.primaryLight,
          tabBarInactiveTintColor: COLORS.textTertiary,
          tabBarLabelStyle: {
            fontSize: 12,
            fontWeight: '600',
          },
          tabBarStyle: {
            backgroundColor: COLORS.surface,
            borderTopColor: COLORS.border,
            borderTopWidth: 1,
            paddingTop: 6,
          },
          tabBarIcon: ({ focused, color, size }) => {
            const [activeIcon, inactiveIcon] = TAB_ICONS[route.name];
            return (
              <Ionicons
                name={focused ? activeIcon : inactiveIcon}
                size={size}
                color={color}
              />
            );
          },
        })}
      >
        <Tab.Screen
          name="Trang chủ"
          component={HomeScreen}
          options={{ tabBarAccessibilityLabel: 'Mở trang chủ' }}
        />
        <Tab.Screen
          name="Danh mục"
          component={CategoryScreen}
          options={{ tabBarAccessibilityLabel: 'Mở danh mục sản phẩm' }}
        />
        <Tab.Screen
          name="Tài khoản"
          component={AccountScreen}
          options={{ tabBarAccessibilityLabel: 'Mở tài khoản' }}
        />
      </Tab.Navigator>

      <TouchableOpacity
        style={styles.luckyWheelButton}
        activeOpacity={0.85}
        accessibilityRole="button"
        accessibilityLabel="Mở vòng quay may mắn"
        onPress={() => navigation.navigate('LuckyWheel')}
      >
        <Ionicons name="gift" size={29} color="#ffffff" />
      </TouchableOpacity>
    </View>
  );
}

export default function AppNavigator() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="Main" component={MainTabs} />
      <Stack.Screen name="Notifications" component={NotificationsScreen} />
      <Stack.Screen name="SupportChat" component={SupportChatScreen} />
      <Stack.Screen name="Yêu thích" component={WishlistScreen} />
      <Stack.Screen name="Giỏ hàng" component={CartScreen} />
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

const styles = StyleSheet.create({
  mainTabsContainer: {
    flex: 1,
  },
  luckyWheelButton: {
    position: 'absolute',
    right: 8,
    bottom: 160,
    width: 58,
    height: 58,
    borderRadius: 29,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#7c3aed',
    borderWidth: 2,
    borderColor: '#c4b5fd',
    shadowColor: '#4c1d95',
    shadowOffset: { width: 0, height: 5 },
    shadowOpacity: 0.34,
    shadowRadius: 8,
    elevation: 10,
    zIndex: 1000,
  },
});
