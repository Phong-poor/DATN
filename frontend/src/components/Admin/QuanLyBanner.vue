<template>
  <div class="page">
    <template v-if="!showForm">
      <!-- TOPBAR -->
    <div class="topbar">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.35-4.35" />
        </svg>
        <input type="text" placeholder="Tìm kiếm banner..." v-model="searchQuery"/>
      </div>
    </div>

    <!-- BREADCRUMB -->
    <div class="breadcrumb">
      <span>HỆ THỐNG</span>
      <span class="sep">›</span>
      <span class="active-crumb">QUẢN LÝ BANNER</span>
    </div>

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div>
        <h1>Quản lý <span class="title-accent">Banner</span></h1>
        <p>Cập nhật và sắp xếp các banner quảng cáo hiển thị trên trang chủ và trang ngành hàng.</p>
      </div>
      <div class="action-row">
        <button class="btn-new" @click="openCreate">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          Thêm banner mới
        </button>
      </div>
    </div>

    <BulkDeleteToolbar
      :selected-count="selectedIds.length"
      :total-count="filteredBanners.length"
      label="banner"
      :loading="isBulkDeleting"
      @clear="clearSelection"
      @delete-selected="removeSelected"
      @delete-all="removeAllFiltered"
    />

    <!-- TABLE CARD -->
    <div class="table-card-wrap">
      <div class="card">
        <table>
          <thead>
            <tr>
              <th class="select-col">
                <input type="checkbox" :checked="allCurrentPageSelected" :disabled="!paginatedBanners.length" @change="toggleCurrentPageSelection" />
              </th>
              <th>MEDIA</th>
              <th>TIÊU ĐỀ / PHỤ ĐỀ</th>
              <th>VỊ TRÍ</th>
              <th>LIÊN KẾT</th>
              <th>LOẠI MEDIA</th>
              <th>TRẠNG THÁI</th>
              <th>THAO TÁC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="empty">
                <span class="spinner-sm"></span>
                Đang tải dữ liệu...
              </td>
            </tr>
            <tr v-else-if="filteredBanners.length === 0">
              <td colspan="8" class="empty">Chưa có banner nào hiển thị.</td>
            </tr>
            <tr v-else v-for="item in paginatedBanners" :key="item.id" :class="{ 'row-selected': selectedIds.includes(item.id) }">
              <td class="select-col">
                <input type="checkbox" :checked="selectedIds.includes(item.id)" @change="toggleItemSelection(item.id)" />
              </td>
              <td>
                <div class="media-preview-container">
                  <video
                    v-if="item.media_type === 'video'"
                    :src="mediaSrc(item.image)"
                    class="thumb"
                    muted
                    playsinline
                    preload="metadata"
                  ></video>
                  <img v-else :src="mediaSrc(item.image)" class="thumb" alt="banner" />
                </div>
              </td>
              <td>
                <div class="title">{{ item.title }}</div>
                <div class="sub">{{ item.subtitle || "-" }}</div>
              </td>
              <td><span class="position-badge">{{ item.position }}</span></td>
              <td>
                <span class="link-text" v-if="item.link_url">{{ item.link_url }}</span>
                <span class="no-link" v-else>—</span>
              </td>
              <td>
                <span class="media-type-tag" :class="item.media_type === 'video' ? 'video' : 'image'">
                  {{ item.media_type === "video" ? "Video" : "Ảnh" }}
                </span>
              </td>
              <td>
                <span class="status-dot" :class="item.is_active ? 'active' : 'draft'">
                  ● {{ item.is_active ? "Hoạt động" : "Ẩn" }}
                </span>
              </td>
              <td>
                <div class="actions">
                  <button class="act-btn" @click="openEdit(item)" title="Sửa banner">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </button>
                  <button class="act-btn danger" @click="remove(item.id)" title="Xóa banner">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                      <polyline points="3 6 5 6 21 6" />
                      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                      <path d="M10 11v6M14 11v6" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    </template>

    <!-- FORM VIEW -->
    <template v-else>
      <div class="inline-form-header">
        <button class="back-btn" @click="closeForm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><path d="M15 18l-6-6 6-6"/></svg>
          Quay lại danh sách
        </button>
        <h1><span class="icon" style="color: #2563eb; margin-right: 8px;">+</span> {{ editingId ? "Cập nhật Banner" : "Thêm Banner mới" }}</h1>
        <p>Điền đầy đủ thông tin để thiết lập banner hiển thị trên hệ thống</p>
      </div>

      <div class="inline-form-body">
        <div class="form-card">
                <div class="form-row">
                  <div class="form-group">
                    <label>TIÊU ĐỀ BANNER <span class="req">*</span></label>
                    <input v-model="form.title" placeholder="VD: Tết Sale Rực Rỡ 2026" />
                  </div>
                  <div class="form-group">
                    <label>PHỤ ĐỀ / MÔ TẢ NGẮN</label>
                    <input v-model="form.subtitle" placeholder="VD: Giảm sâu tới 50% toàn bộ sản phẩm" />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>DÒNG NHÃN HERO</label>
                    <input v-model="form.eyebrow" placeholder="VD: PREMIUM LAPTOP STORE 2026" />
                  </div>
                  <div class="form-group">
                    <label>DÒNG CHỮ XANH NỔI BẬT</label>
                    <input v-model="form.highlight" placeholder="VD: Sự Tinh Tế Chuyên Sâu" />
                  </div>
                </div>

                <div class="form-group">
                  <label>MÔ TẢ HERO</label>
                  <textarea v-model="form.description" rows="3" placeholder="Nhập đoạn mô tả hiển thị dưới tiêu đề banner"></textarea>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>SẢN PHẨM THẬT GẮN BANNER</label>
                    <select v-model="form.product_id" @change="syncProductLink">
                      <option value="">Chọn sản phẩm hiển thị bên phải</option>
                      <option v-for="p in productOptions" :key="p.id" :value="p.id">
                        {{ p.name }} - {{ formatPrice(p.price) }}
                      </option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>NÚT CHÍNH / NÚT PHỤ</label>
                    <div class="inline-inputs">
                      <input v-model="form.primary_label" placeholder="Mua ngay" />
                      <input v-model="form.secondary_label" placeholder="Xem bộ sưu tập" />
                    </div>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>ĐƯỜNG DẪN LIÊN KẾT (URL)</label>
                    <input v-model="form.link_url" placeholder="VD: /products/macbook-pro-m3" />
                  </div>
                  <div class="form-group">
                    <label>VỊ TRÍ HIỂN THỊ (SẮP XẾP)</label>
                    <input v-model.number="form.position" type="number" min="0" />
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label>NGÀY BẮT ĐẦU</label>
                    <input v-model="form.starts_at" type="datetime-local" />
                  </div>
                  <div class="form-group">
                    <label>NGÀY KẾT THÚC</label>
                    <input v-model="form.ends_at" type="datetime-local" />
                  </div>
                </div>

                <div class="form-group">
                  <label>MEDIA DESKTOP (ẢNH/VIDEO) <span class="req" v-if="!editingId">*</span></label>
                  <div class="upload-zone" @click="desktopMediaRef.click()">
                    <input ref="desktopMediaRef" type="file" accept="image/*,video/*" @change="onFileChange($event, 'image')" style="display:none" />
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                      <polyline points="17 8 12 3 7 8" />
                      <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                    <p>Kéo thả hoặc <span>bấm để chọn media</span></p>
                    <small>PNG, JPG, WEBP, MP4 — tối đa 5MB</small>
                    <p v-if="form.image && form.image.name" style="margin-top: 10px; font-weight: 600; color: #2563eb; font-size: 13px;">✓ {{ form.image.name }}</p>
                  </div>
                </div>
                
                <div class="form-group" style="margin-top: 24px;">
                  <label>MEDIA MOBILE (ẢNH/VIDEO)</label>
                  <div class="upload-zone" @click="mobileMediaRef.click()">
                    <input ref="mobileMediaRef" type="file" accept="image/*,video/*" @change="onFileChange($event, 'mobile_image')" style="display:none" />
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                      <polyline points="17 8 12 3 7 8" />
                      <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                    <p>Kéo thả hoặc <span>bấm để chọn media</span></p>
                    <small>PNG, JPG, WEBP, MP4 — tối đa 5MB</small>
                    <p v-if="form.mobile_image && form.mobile_image.name" style="margin-top: 10px; font-weight: 600; color: #2563eb; font-size: 13px;">✓ {{ form.mobile_image.name }}</p>
                  </div>
                </div>
                
                <div style="height: 24px;"></div>

                <div class="form-group">
                  <label>TRẠNG THÁI HIỂN THỊ</label>
                  <div class="toggle-group">
                    <button type="button" class="toggle-btn"
                      :class="{ 'tg-green': form.is_active }"
                      @click="form.is_active = true">
                      <span class="tdot"></span>Hoạt động
                    </button>
                    <button type="button" class="toggle-btn" :class="{ 'tg-yellow': !form.is_active }"
                      @click="form.is_active = false">
                      <span class="tdot"></span>Ẩn banner
                    </button>
                  </div>
                </div>

                <div class="banner-preview">
                  <div class="preview-copy">
                    <span>{{ form.eyebrow || 'PREMIUM LAPTOP STORE 2026' }}</span>
                    <h4>{{ form.title || 'Sức Mạnh Hội Tụ' }}</h4>
                    <strong>{{ form.highlight || form.subtitle || 'Sự Tinh Tế Chuyên Sâu' }}</strong>
                    <p>{{ form.description || form.subtitle || 'Laptop cao cấp chế tác riêng cho nhà sáng tạo, game thủ chuyên nghiệp và kỹ sư công nghệ.' }}</p>
                  </div>
                  <div class="preview-product">
                    <img :src="selectedProduct?.image || '/hero_3d_laptop.png'" alt="preview product" />
                    <div>
                      <b>{{ selectedProduct?.name || 'Chọn sản phẩm thật' }}</b>
                      <small>{{ selectedProduct ? formatPrice(selectedProduct.price) : 'Nút Thanh toán ngay sẽ lấy sản phẩm này' }}</small>
                      <button type="button">Thanh toán ngay</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 32px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                <button class="btn-cancel" @click="closeForm">Hủy</button>
                <button class="btn-submit" :disabled="saving" @click="save">
                  <svg v-if="saving" class="spin" viewBox="0 0 24 24" fill="none"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                  {{ saving ? "Đang lưu..." : (editingId ? "Lưu thay đổi" : "Tạo banner") }}
                </button>
              </div>
        </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref, computed, watch } from "vue";
import api from "@/services/api";
import { productImageUrl, storageUrl } from "@/services/urls";
import swal from "@/services/swal";
import BulkDeleteToolbar from "./ThanhXoaHangLoat.vue";
import { useAdminBulkDelete } from "@/services/adminBulkDelete";

const loading = ref(false);
const saving = ref(false);
const showForm = ref(false);
const editingId = ref(null);
const desktopMediaRef = ref(null);
const mobileMediaRef = ref(null);
const banners = ref([]);
const products = ref([]);
const searchQuery = ref("");

// ─── PAGINATION ──────────────────────
const currentPage = ref(1);
const pageSize = 5;

const defaultForm = () => ({
  title: "",
  subtitle: "",
  eyebrow: "PREMIUM LAPTOP STORE 2026",
  highlight: "Sự Tinh Tế Chuyên Sâu",
  description: "Laptop cao cấp chế tác riêng cho nhà sáng tạo, game thủ chuyên nghiệp và kỹ sư công nghệ.",
  link_url: "",
  product_id: "",
  primary_label: "Mua ngay",
  secondary_label: "Xem bộ sưu tập",
  product_badge: "TRENDING NOW",
  product_feature: "RTX 40-Series",
  position: 0,
  is_active: true,
  starts_at: "",
  ends_at: "",
  image: null,
  mobile_image: null,
});

const form = ref(defaultForm());

const mediaSrc = (path) => storageUrl(path);

const productOptions = computed(() => {
  return products.value.map((p) => {
    const variants = Array.isArray(p.bien_thes) ? p.bien_thes : [];
    const variant = variants.find(v => Number(v.soluong || 0) > 0) || variants[0] || {};
    return {
      id: p.id_sanpham,
      name: p.tenSP || `Sản phẩm #${p.id_sanpham}`,
      price: Number(variant.gia || 0),
      variantId: variant.id_bienthe || "",
      brand: p.thuong_hieu?.ten_thuonghieu || "",
      image: productImageUrl(p, variant),
    };
  });
});

const selectedProduct = computed(() => {
  return productOptions.value.find(p => String(p.id) === String(form.value.product_id)) || null;
});

const formatPrice = (price) => {
  return new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(Number(price || 0));
};

const syncProductLink = () => {
  if (form.value.product_id) {
    form.value.link_url = `/san-pham/${form.value.product_id}`;
  }
};

const fetchProducts = async () => {
  try {
    const { data } = await api.get("/sanpham", { skipGlobalLoader: true });
    products.value = Array.isArray(data) ? data : (data?.data || []);
  } catch (e) {
    products.value = [];
  }
};

const fetchData = async () => {
  loading.value = true;
  try {
    const { data } = await api.get("/admin/banners");
    banners.value = Array.isArray(data) ? data : [];
  } catch (e) {
    swal.error("Không thể tải banner", e?.response?.data?.message || "Lỗi khi lấy dữ liệu banner");
    banners.value = [];
  } finally {
    loading.value = false;
  }
};

const filteredBanners = computed(() => {
  if (!searchQuery.value) return banners.value;
  const q = searchQuery.value.toLowerCase();
  return banners.value.filter(
    (b) =>
      b.title.toLowerCase().includes(q) ||
      (b.subtitle && b.subtitle.toLowerCase().includes(q))
  );
});

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredBanners.value.length / pageSize))
);

const paginatedBanners = computed(() => {
  const start = (currentPage.value - 1) * pageSize;
  return filteredBanners.value.slice(start, start + pageSize);
});

const pageItems = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;

  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  if (current <= 3) {
    return [1, 2, 3, '...', total - 2, total - 1, total];
  }
  if (current >= total - 2) {
    return [1, 2, 3, '...', total - 2, total - 1, total];
  }
  return [1, '...', current - 1, current, current + 1, '...', total];
});

const goToPage = (page) => {
  if (page < 1) {
    currentPage.value = 1;
    return;
  }
  if (page > totalPages.value) {
    currentPage.value = totalPages.value;
    return;
  }
  currentPage.value = page;
};

watch(searchQuery, () => {
  currentPage.value = 1;
});

const {
  selectedIds,
  isBulkDeleting,
  allCurrentPageSelected,
  toggleItemSelection,
  toggleCurrentPageSelection,
  clearSelection,
  removeSelected,
  removeAllFiltered,
} = useAdminBulkDelete({
  items: banners,
  filteredItems: filteredBanners,
  pageItems: paginatedBanners,
  getId: item => item.id,
  endpoint: id => `/admin/banners/${id}`,
  entityLabel: 'banner',
  fetchItems: fetchData,
});

const openCreate = () => {
  editingId.value = null;
  form.value = defaultForm();
  showForm.value = true;
};

const openEdit = (item) => {
  editingId.value = item.id;
  form.value = {
    ...defaultForm(),
    title: item.title || "",
    subtitle: item.subtitle || "",
    eyebrow: item.eyebrow || "PREMIUM LAPTOP STORE 2026",
    highlight: item.highlight || item.subtitle || "Sự Tinh Tế Chuyên Sâu",
    description: item.description || "",
    link_url: item.link_url || "",
    product_id: item.product_id || "",
    primary_label: item.primary_label || "Mua ngay",
    secondary_label: item.secondary_label || "Xem bộ sưu tập",
    product_badge: item.product_badge || "TRENDING NOW",
    product_feature: item.product_feature || "RTX 40-Series",
    position: Number(item.position || 0),
    is_active: Boolean(item.is_active),
    starts_at: item.starts_at ? item.starts_at.slice(0, 16) : "",
    ends_at: item.ends_at ? item.ends_at.slice(0, 16) : "",
  };
  showForm.value = true;
};

const closeForm = () => {
  showForm.value = false;
};

const onFileChange = (event, key) => {
  const file = event.target?.files?.[0] || null;
  if (file) {
    const ok = file.type.startsWith("image/") || file.type.startsWith("video/");
    if (!ok) {
      swal.error("Không hợp lệ", "Chỉ hỗ trợ file ảnh hoặc video");
      event.target.value = "";
      return;
    }
  }
  form.value[key] = file;
};

const toFormData = () => {
  const fd = new FormData();
  fd.append("title", form.value.title || "");
  fd.append("subtitle", form.value.subtitle || "");
  fd.append("eyebrow", form.value.eyebrow || "");
  fd.append("highlight", form.value.highlight || "");
  fd.append("description", form.value.description || "");
  fd.append("link_url", form.value.link_url || "");
  fd.append("product_id", form.value.product_id ? String(form.value.product_id) : "");
  fd.append("primary_label", form.value.primary_label || "");
  fd.append("secondary_label", form.value.secondary_label || "");
  fd.append("product_badge", form.value.product_badge || "");
  fd.append("product_feature", form.value.product_feature || "");
  fd.append("position", String(form.value.position || 0));
  fd.append("is_active", form.value.is_active ? "1" : "0");
  if (form.value.starts_at) fd.append("starts_at", form.value.starts_at);
  if (form.value.ends_at) fd.append("ends_at", form.value.ends_at);
  if (form.value.image) fd.append("image", form.value.image);
  if (form.value.mobile_image) fd.append("mobile_image", form.value.mobile_image);
  return fd;
};

const save = async () => {
  if (!form.value.title) {
    swal.error("Thiếu thông tin", "Vui lòng nhập tiêu đề banner");
    return;
  }
  if (!editingId.value && !form.value.image) {
    swal.error("Thiếu media", "Vui lòng chọn media desktop");
    return;
  }

  saving.value = true;
  try {
    const payload = toFormData();
    const config = { headers: { "Content-Type": "multipart/form-data" } };
    if (editingId.value) {
      await api.post(`/admin/banners/${editingId.value}`, payload, config);
    } else {
      await api.post("/admin/banners", payload, config);
    }
    closeForm();
    await fetchData();
    swal.success("Thành công", editingId.value ? "Cập nhật banner thành công" : "Thêm banner mới thành công");
  } catch (e) {
    swal.error("Thất bại", e?.response?.data?.message || "Lưu banner thất bại");
  } finally {
    saving.value = false;
  }
};

const remove = async (id) => {
  const ok = await swal.confirm("Xác nhận xóa", "Bạn có chắc chắn muốn xóa banner này không? Thao tác này không thể hoàn tác.");
  if (!ok) return;
  try {
    await api.delete(`/admin/banners/${id}`);
    await fetchData();
    if (paginatedBanners.value.length === 0 && currentPage.value > 1) {
      currentPage.value--;
    }
    swal.success("Đã xóa", "Xóa banner thành công");
  } catch (e) {
    swal.error("Thất bại", e?.response?.data?.message || "Không thể xóa banner");
  }
};

onMounted(() => {
  fetchData();
  fetchProducts();
});
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.page {
  background: #f0f4ff;
  min-height: 100vh;
  font-family: 'Be Vietnam Pro', sans-serif;
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 8px 14px;
  width: 260px;
}

.search-box svg {
  width: 15px;
  height: 15px;
  stroke: #94a3b8;
  stroke-width: 2;
  fill: none;
  flex-shrink: 0;
}

.search-box input {
  border: none;
  outline: none;
  font-size: 13px;
  color: #1e293b;
  background: transparent;
  width: 100%;
}

.breadcrumb {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}

.sep {
  margin: 0 6px;
}

.active-crumb {
  color: #2563eb;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.page-header h1 {
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.02em;
}

.title-accent {
  color: #2563eb;
}

.page-header p {
  font-size: 13px;
  color: #64748b;
  margin: 3px 0 0;
  line-height: 1.5;
}

.action-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.btn-new {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 10px 18px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: white;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  font-family: inherit;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
  transition: transform .15s, opacity .15s;
}

.btn-new svg {
  width: 14px;
  height: 14px;
}

.btn-new:hover {
  opacity: .9;
  transform: translateY(-1px);
}

.table-card-wrap {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e8edf5;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
}

.card {
  padding: 0;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead tr {
  background: #f8faff;
  border-bottom: 1px solid #e8edf5;
}

th {
  padding: 12px 18px;
  font-size: 11px;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.6px;
  text-align: left;
  text-transform: uppercase;
}

tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s;
}

tbody tr:last-child {
  border-bottom: none;
}

tbody tr:hover {
  background: #fafbff;
}

tbody tr.row-selected {
  background: #eff6ff;
}

td {
  padding: 14px 18px;
  vertical-align: middle;
}

.select-col {
  width: 44px;
  text-align: center;
}

.select-col input {
  width: 16px;
  height: 16px;
  accent-color: #2563eb;
  cursor: pointer;
}

.media-preview-container {
  width: 96px;
  height: 54px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #cbd5e1;
  background: #0f172a;
}

.thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.title {
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
}

.sub {
  color: #64748b;
  font-size: 12px;
  margin-top: 2px;
}

.position-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 6px;
  background: #f1f5f9;
  color: #475569;
  font-size: 12px;
  font-weight: 700;
}

.link-text {
  font-size: 12.5px;
  color: #2563eb;
  font-weight: 500;
}

.no-link {
  color: #94a3b8;
}

.media-type-tag {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
}

.media-type-tag.video {
  background: #ede9fe;
  color: #1d4ed8;
}

.media-type-tag.image {
  background: #e0f2fe;
  color: #0369a1;
}

.status-dot {
  font-size: 11.5px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  display: inline-block;
}

.status-dot.active {
  color: #2563eb;
  background: #dcfce7;
}

.status-dot.draft {
  color: #dc2626;
  background: #fee2e2;
}

.actions {
  display: flex;
  gap: 6px;
}

.act-btn {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  color: #64748b;
}

.act-btn:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.act-btn svg {
  width: 13px;
  height: 13px;
}

.act-btn.danger:hover {
  background: #fef2f2;
  border-color: #fca5a5;
  color: #ef4444;
}

.empty {
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
  padding: 28px;
}

.spinner-sm {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid #cbd5e1;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  margin-right: 6px;
  vertical-align: middle;
}

/* ═══ MODAL ═══ */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal {
  background: #fff;
  border-radius: 20px;
  width: 100%;
  max-width: 760px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 22px 18px;
  border-bottom: 1px solid #f1f5f9;
}

.modal-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-add {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
}

.icon-edit {
  background: linear-gradient(135deg, #f59e0b, #f97316);
}

.modal-title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.modal-subtitle {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 2px;
}

.modal-close {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #64748b;
  font-weight: bold;
}

.modal-close:hover {
  background: #fee2e2;
  color: #ef4444;
}

.modal-body {
  padding: 20px 22px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.form-group label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.8px;
  color: #64748b;
  text-transform: uppercase;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.req {
  color: #ef4444;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 9px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 13px;
  color: #1e293b;
  outline: none;
  font-family: inherit;
  width: 100%;
  background: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input,
.form-group select {
  height: 38px;
}

.form-group textarea {
  min-height: 82px;
  resize: vertical;
  line-height: 1.45;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.inline-inputs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.file-input-wrapper {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.file-input-wrapper input[type="file"] {
  height: auto;
  padding: 6px 10px;
}

.file-hint {
  font-size: 11px;
  color: #2563eb;
  font-weight: 600;
}

.toggle-group {
  display: flex;
  gap: 8px;
}

.toggle-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 9px;
  border: 1.5px solid #e2e8f0;
  background: #f8fafc;
  font-size: 12.5px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all .15s;
  font-family: inherit;
}

.toggle-btn:hover {
  background: #eef2ff;
  border-color: #c7d2fe;
  color: #2563eb;
}

.tdot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #cbd5e1;
  display: inline-block;
}

.toggle-btn.tg-green {
  border-color: #86efac;
  background: #f0fdf4;
  color: #2563eb;
  font-weight: 600;
}

.toggle-btn.tg-green .tdot {
  background: #2563eb;
}

.toggle-btn.tg-yellow {
  border-color: #fca5a5;
  background: #fef2f2;
  color: #dc2626;
  font-weight: 600;
}

.toggle-btn.tg-yellow .tdot {
  background: #dc2626;
}

.banner-preview {
  display: grid;
  grid-template-columns: 1fr 1.05fr;
  gap: 16px;
  align-items: center;
  padding: 18px;
  border-radius: 18px;
  background:
    linear-gradient(90deg, rgba(2, 6, 23, 0.82), rgba(15, 23, 42, 0.58)),
    url('/Gemini_Generated_Image_v5vppjv5vppjv5vp (1).png') center/cover;
  color: #fff;
  min-height: 210px;
  overflow: hidden;
}

.preview-copy span {
  display: inline-flex;
  padding: 5px 10px;
  border-radius: 999px;
  background: rgba(37, 99, 235, 0.35);
  color: #38bdf8;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
  margin-bottom: 10px;
}

.preview-copy h4 {
  margin: 0;
  color: #fff;
  font-size: 24px;
  line-height: 1.05;
  font-weight: 900;
}

.preview-copy strong {
  display: block;
  color: #3b82f6;
  font-size: 22px;
  line-height: 1.1;
  margin-top: 2px;
}

.preview-copy p {
  margin: 10px 0 0;
  color: #cbd5e1;
  font-size: 12px;
  line-height: 1.45;
}

.preview-product {
  position: relative;
  background: rgba(255, 255, 255, 0.94);
  border-radius: 16px;
  padding: 12px;
  color: #0f172a;
  box-shadow: 0 22px 45px rgba(0, 0, 0, 0.25);
}

.preview-product img {
  width: 100%;
  height: 118px;
  object-fit: contain;
  background: #f8fafc;
  border-radius: 12px;
}

.preview-product b,
.preview-product small {
  display: block;
}

.preview-product b {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.3;
}

.preview-product small {
  margin-top: 2px;
  color: #ef4444;
  font-weight: 800;
}

.preview-product button {
  margin-top: 8px;
  width: 100%;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #fff;
  height: 34px;
  font-weight: 800;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 22px;
  border-top: 1px solid #f1f5f9;
  background: #fafbff;
}

.btn-cancel {
  padding: 9px 18px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  background: #fff;
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  font-family: inherit;
}

.btn-submit {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 9px 20px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
  transition: transform 0.15s;
  font-family: inherit;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-submit svg {
  width: 14px;
  height: 14px;
}

.spin {
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* TRANSITIONS */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.slide-up-leave-active {
  transition: all 0.2s ease;
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(28px) scale(0.97);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.98);
}

/* ═══ INLINE FORM ═══ */
.inline-form-header {
  margin-bottom: 24px;
}
.inline-form-header h1 {
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  margin: 16px 0 6px;
  display: flex;
  align-items: center;
}
.inline-form-header p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}
.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 8px;
  border: 1.5px solid #e2e8f0;
  background: white;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all .2s;
  font-family: inherit;
}
.back-btn:hover {
  border-color: #2563eb;
  color: #2563eb;
  background: #eff6ff;
}
.form-card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e8edf5;
  padding: 32px 36px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
}
.upload-zone {
  border: 1.5px dashed #cbd5e1;
  border-radius: 12px;
  background: #f8fafc;
  padding: 32px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.upload-zone:hover {
  border-color: #3b82f6;
  background: #eef2ff;
}
.upload-zone svg {
  width: 32px;
  height: 32px;
  color: #3b82f6;
  margin-bottom: 8px;
}
.upload-zone p {
  margin: 0;
  font-size: 14px;
  color: #475569;
}
.upload-zone span {
  color: #3b82f6;
  font-weight: 600;
  text-decoration: underline;
}
.upload-zone small {
  color: #94a3b8;
  font-size: 12px;
}
</style>
