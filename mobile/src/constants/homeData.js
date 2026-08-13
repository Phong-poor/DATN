/**
 * Static data for HomeScreen
 * Extracted to prevent recreation on every render
 */

// Laptop Segments (Specialized categories)
export const laptopSegments = [
  {
    id: 1,
    title: 'Gaming Powerhouse',
    desc: 'RTX 4060+ & 165Hz displays',
    image: 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?w=400&q=80',
  },
  {
    id: 2,
    title: 'Creative Workstation',
    desc: '4K OLED & GPU rendering',
    image: 'https://images.unsplash.com/photo-1593642532400-2682810df593?w=400&q=80',
  },
  {
    id: 3,
    title: 'Business Ultrabook',
    desc: 'Lightweight & all-day battery',
    image: 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=400&q=80',
  },
  {
    id: 4,
    title: 'Budget Student',
    desc: 'Affordable & reliable specs',
    image: 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=400&q=80',
  },
];

// Setup Inspirations
export const setups = [
  {
    id: 1,
    title: 'Gaming Console Room',
    desc: 'Bàn máy tính RGB cực ngầu',
    image: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=500&q=80',
  },
  {
    id: 2,
    title: 'Creative Studio Pod',
    desc: 'Tối ưu cho nhạc sĩ & editor',
    image: 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=500&q=80',
  },
  {
    id: 3,
    title: 'Ultimate Workdesk',
    desc: 'Phong cách tối giản tinh tế',
    image: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&q=80',
  }
];

// Testimonials
export const testimonials = [
  {
    id: 1,
    name: 'Anh Tuấn',
    role: 'Lập trình viên Senior',
    comment: 'Máy trạm mua ở đây chạy Docker cực êm, tản nhiệt rất mát. Dịch vụ hỗ trợ 24/7 rất chuyên nghiệp.',
    rating: '⭐⭐⭐⭐⭐'
  },
  {
    id: 2,
    name: 'Chị Vy',
    role: 'UI/UX Designer',
    comment: 'Màn hình OLED màu sắc chuẩn chỉnh 100% DCI-P3. Phí ship siêu tốc hỏa tốc 2h nhận được ngay.',
    rating: '⭐⭐⭐⭐⭐'
  }
];

// Fallback blogs for when news API fails
export const fallbackBlogs = [
  {
    id: 1,
    category: 'Đánh giá',
    title: 'Review MacBook Air M4 thế hệ mới: Cực đỉnh NPU AI',
    date: '07/07/2026',
    image: 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=300&q=80'
  },
  {
    id: 2,
    category: 'Tư vấn',
    title: 'Top 5 laptop đồ họa màn hình OLED tốt nhất năm 2026',
    date: '05/07/2026',
    image: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=300&q=80'
  },
  {
    id: 3,
    category: 'Xu hướng',
    title: 'Setup góc làm việc công nghệ tối giản: Nâng tầm sáng tạo',
    date: '02/07/2026',
    image: 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=300&q=80'
  }
];

// Brand filter tabs
export const brandTabs = [
  { id: 'all', label: 'Tất cả' },
  { id: 'macbook', label: 'MacBook' },
  { id: 'thinkpad', label: 'ThinkPad' },
  { id: 'asus', label: 'ASUS' },
  { id: 'hp', label: 'HP' }
];

/**
 * Helper to map DB categories to high-res Unsplash graphics
 */
export const getCategoryDetails = (catName) => {
  const name = (catName || '').toLowerCase();
  
  if (name.includes('gaming')) {
    return {
      desc: 'RTX Graphics & High Hz',
      image: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=400&q=80',
    };
  }
  if (name.includes('macbook') || name.includes('apple')) {
    return {
      desc: 'Slim Design & M-Chips',
      image: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400&q=80',
    };
  }
  if (name.includes('workstation') || name.includes('trạm')) {
    return {
      desc: 'Coding & Graphic Heavy',
      image: 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=400&q=80',
    };
  }
  if (name.includes('văn phòng') || name.includes('office') || name.includes('mỏng nhẹ')) {
    return {
      desc: 'Ultra-thin & Long Battery',
      image: 'https://images.unsplash.com/photo-1585776245991-cf89dd7fc73a?w=400&q=80',
    };
  }
  return {
    desc: 'Laptop chính hãng cao cấp',
    image: 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=400&q=80',
  };
};
