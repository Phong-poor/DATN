<script setup>


const posts = [
  {
    title: "VinaBook Pro 2026: Kỷ nguyên mới của hiệu suất di động",
    category: "ĐÁNH GIÁ",
    img: "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800",
    date: "12 Tháng 12, 2026"
  },
  {
    title: "Trí tuệ nhân tạo VinaAI tích hợp sâu vào hệ sinh thái",
    category: "TIN TỨC",
    img: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=800",
    date: "12 Tháng 12, 2026"
  },
  {
    title: "5 cách tối ưu hóa không gian làm việc cho Designer",
    category: "MẸO VẶT",
    img: "https://images.unsplash.com/photo-1492724441997-5dc865305da7?w=800",
    date: "12 Tháng 12, 2026"
  },
  {
    title: "Sự trỗi dậy của công nghệ xanh tại thị trường Việt Nam",
    category: "XU HƯỚNG",
    img: "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800",
    date: "08 Tháng 12, 2026"
  },
  {
    title: "VinaPhone 18 Pro: Camera 200MP liệu có thực sự cần thiết?",
    category: "ĐÁNH GIÁ",
    img: "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800",
    date: "07 Tháng 12, 2026"
  },
  {
    title: "Hội nghị VinaDev 2026 chính thức mở đăng ký",
    category: "TIN TỨC",
    img: "https://images.unsplash.com/photo-1518779578993-ec3579fee39f?w=800",
    date: "05 Tháng 12, 2026"
  }
]
</script>

<template>

  <section class="news-page">

    <!-- HEADER -->
    <div class="top">
      <span class="badge">TẠP CHÍ CÔNG NGHỆ 2026</span>
      <h1>Tin tức <span>công nghệ.</span></h1>

      <div class="tabs">
        <span class="active">Mới nhất</span>
        <span>Xu hướng</span>
        <span>Đánh giá</span>
      </div>
    </div>

    <div class="container">

      <!-- LEFT -->
      <div class="left">

        <div class="grid">
          <div class="card" v-for="(p, i) in posts" :key="i">
            <img :src="p.img" />

            <span class="category">{{ p.category }}</span>

            <small>{{ p.date }}</small>

            <h3>{{ p.title }}</h3>

            <a href="#">XEM THÊM →</a>
          </div>
        </div>

        <!-- PAGINATION -->
        <div class="pagination">
          <button>‹</button>
          <button class="active">1</button>
          <button>2</button>
          <button>3</button>
          <button>›</button>
        </div>

      </div>

      <!-- RIGHT -->
      <div class="right">

        <!-- MOST READ -->
        <div class="box">
          <h4>• Bài viết phổ biến</h4>

          <div class="popular">
            <div class="item">
              <img src="https://randomuser.me/api/portraits/women/32.jpg" />
              <div>
                <p>Xu hướng AI trong thiết kế UI</p>
                <span>36 lượt xem</span>
              </div>
            </div>

            <div class="item">
              <img src="https://randomuser.me/api/portraits/men/12.jpg" />
              <div>
                <p>Top 10 laptop đáng mua 2026</p>
                <span>21 lượt xem</span>
              </div>
            </div>

            <div class="item">
              <img src="https://randomuser.me/api/portraits/men/44.jpg" />
              <div>
                <p>VinaTech và bảo mật dữ liệu</p>
                <span>18 lượt xem</span>
              </div>
            </div>
          </div>
        </div>

        <!-- CATEGORY -->
        <div class="box">
          <h4>• Chuyên mục</h4>
          <div class="tags">
            <span>Đánh giá</span>
            <span>Tin tức</span>
            <span>Mẹo vặt</span>
            <span>Xu hướng</span>
          </div>
        </div>

        <!-- CTA -->
        <div class="cta">
          <h3>Đăng ký nhận tin</h3>
          <p>Cập nhật công nghệ mới nhất từ VinaTech</p>
          <input placeholder="Email của bạn" />
          <button>THAM GIA NGAY</button>
        </div>

      </div>

    </div>

  </section>

</template>

<style scoped>
/* PAGE */
.news-page {
  background: #f5f7fb;
  padding: 40px 80px;
}

/* TOP */
.top {
  margin-bottom: 30px;
}

.badge {
  font-size: 11px;
  color: #2563eb;
}

.top h1 {
  font-size: 36px;
  font-weight: 800;
}

.top h1 span {
  color: #4f46e5;
}

.tabs {
  margin-top: 10px;
}

.tabs span {
  margin-right: 20px;
  font-size: 13px;
  cursor: pointer;
}

.tabs .active {
  font-weight: bold;
  color: #2563eb;
}

/* LAYOUT */
.container {
  display: grid;
  grid-template-columns: 3fr 1fr;
  gap: 40px;
}

/* GRID */
.grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

/* CARD */
.card {
  background: white;
  border-radius: 16px;
  padding: 15px;
  transition: 0.3s;
}

.card:hover {
  transform: translateY(-5px);
}

.card img {
  width: 100%;
  height: 140px;
  object-fit: cover;
  border-radius: 12px;
}

.category {
  font-size: 10px;
  color: #2563eb;
  font-weight: 600;
}

.card h3 {
  font-size: 14px;
  margin: 8px 0;
}

.card a {
  font-size: 12px;
  color: #2563eb;
  text-decoration: none;
}

/* PAGINATION */
.pagination {
  margin-top: 30px;
}

.pagination button {
  margin-right: 6px;
  padding: 6px 10px;
  border: none;
  background: #e2e8f0;
}

.pagination .active {
  background: #2563eb;
  color: white;
}

/* RIGHT */
.box {
  background: white;
  padding: 20px;
  border-radius: 12px;
  margin-bottom: 20px;
}

.popular .item {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}

.popular img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
}

/* TAGS */
.tags span {
  background: #e2e8f0;
  padding: 6px 10px;
  margin: 4px;
  display: inline-block;
  font-size: 12px;
  border-radius: 6px;
}

/* CTA */
.cta {
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  color: white;
  padding: 20px;
  border-radius: 16px;
}

.cta input {
  width: 95%;
  padding: 8px;
  margin: 10px 0;
  border: none;
  border-radius: 6px;
}

.cta button {
  width: 100%;
  padding: 10px;
  border: none;
  background: white;
  color: #2563eb;
  border-radius: 6px;
}

/* MOBILE */
@media (max-width:768px) {
  .container {
    grid-template-columns: 1fr;
  }

  .grid {
    grid-template-columns: 1fr;
  }
}
</style>