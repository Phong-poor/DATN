<template>
  <div class="page">

    <!-- ═══════════════════════════════════
         VIEW: DANH SÁCH KHUYẾN MÃI
    ═══════════════════════════════════ -->
    <template v-if="currentView === 'list'">

      <!-- TOPBAR -->
      <div class="topbar">
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
          </svg>
          <input type="text" placeholder="Tìm kiếm chương trình..." v-model="searchQuery" />
        </div>
        <div class="topbar-right">
          <button class="icon-btn">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
              <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            <span class="notif-dot"></span>
          </button>
          <button class="icon-btn"><svg viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="3" />
              <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 19.07a10 10 0 0 1 0-14.14" />
            </svg></button>
          <div class="admin-info">
            <div>
              <p class="admin-name">Admin Vina</p>
              <p class="admin-role">Quản trị viên</p>
            </div>
            <div class="admin-avatar">AV</div>
          </div>
        </div>
      </div>

      <!-- TOAST -->
      <transition name="toast">
        <div class="toast" v-if="toast.show" :class="toast.type">
          <svg v-if="toast.type === 'success'" viewBox="0 0 24 24" fill="none">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
          {{ toast.msg }}
        </div>
      </transition>

      <!-- TABS -->
      <div class="page-tabs-wrap">
        <div class="page-tabs">
          <button class="page-tab" :class="{ active: currentTab === 'promotions' }" @click="currentTab = 'promotions'">Danh sách khuyến mãi</button>
          <button class="page-tab" :class="{ active: currentTab === 'events' }" @click="currentTab = 'events'">Chiến dịch và sự kiện</button>
        </div>
      </div>

    <!-- STATS ROW -->
    <div class="stats-row">
      <div class="stat-card stat-active">
        <p class="stat-label">ĐANG HOẠT ĐỘNG</p>
        <h2 class="stat-value">{{ activeCount }}</h2>
        <p class="stat-sub green">↑ Tổng khuyến mãi: {{ promos.length }}</p>
      </div>
      <div class="stat-card stat-budget">
        <p class="stat-label">TỔNG NGÂN SÁCH SALE</p>
        <h2 class="stat-value">2.4B <span class="stat-unit">VNĐ</span></h2>
        <div class="stat-bar"><div class="stat-bar-fill" style="width:72%"></div></div>
      </div>
      <div class="stat-card stat-card-gradient">
        <p class="stat-card-tag">Chiến dịch tiếp theo</p>
        <template v-if="nextHoliday">
          <p class="stat-card-desc">
            <strong>{{ nextHoliday.name }}</strong> sẽ bắt đầu sau
            <strong>{{ nextHoliday.daysLeft === 0 ? 'hôm nay' : nextHoliday.daysLeft + ' ngày nữa' }}</strong>.
          </p>
          <p class="stat-card-subdesc">Mã: <span class="stat-code-badge">{{ nextHoliday.code }}</span></p>
        </template>
        <p class="stat-card-desc" v-else>Không có sự kiện sắp tới.</p>
        <button class="stat-card-btn" @click="goToEvents">Xem chi tiết</button>
      </div>
    </div>

      <!-- LIST HEADER -->
      <div class="list-header">
        <div>
          <h2 class="list-title">{{ currentTab === 'promotions' ? 'Danh sách Khuyến mãi' : 'Chiến dịch và sự kiện' }}</h2>
          <p class="list-sub">{{ currentTab === 'promotions' ? 'Quản lý các chương trình ưu đãi và giảm giá toàn hệ thống.' : 'Quản lý các sự kiện lặp lại hằng năm tự động gửi mã khuyến mãi.' }}</p>
        </div>
        <div class="list-actions">
          <button class="btn-filter">
            <svg viewBox="0 0 24 24" fill="none">
              <line x1="4" y1="6" x2="20" y2="6" />
              <line x1="8" y1="12" x2="16" y2="12" />
              <line x1="11" y1="18" x2="13" y2="18" />
            </svg>
            Lọc
          </button>
          <button class="btn-primary" @click="openCreate">
            <svg viewBox="0 0 24 24" fill="none">
              <line x1="12" y1="5" x2="12" y2="19" />
              <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            {{ currentTab === 'events' ? 'Tạo chiến dịch' : 'Tạo mới' }}
          </button>
        </div>
      </div>

      <BulkDeleteToolbar v-if="currentTab === 'promotions'" :selected-count="selectedIds.length" :total-count="filteredPromos.length" label="khuyến mãi"
        :loading="isBulkDeleting" @clear="clearSelection" @delete-selected="removeSelected"
        @delete-all="removeAllFiltered" />

      <!-- TABLE -->
      <div class="table-card" v-if="currentTab === 'promotions'">
        <table>
          <thead>
            <tr>
              <th class="select-col">
                <input type="checkbox" :checked="allCurrentPageSelected" :disabled="!filteredPromos.length"
                  @change="toggleCurrentPageSelection" />
              </th>
              <th>CHƯƠNG TRÌNH</th>
              <th>LOẠI ƯU ĐÃI</th>
              <th>BẮT ĐẦU</th>
              <th>KẾT THÚC</th>
              <th>HÌNH THỨC</th>
              <th>TRẠNG THÁI</th>
              <th>THAO TÁC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="empty-row">
                <div class="loading-spinner"></div>
                Đang tải...
              </td>
            </tr>
            <tr v-else-if="filteredPromos.length === 0">
              <td colspan="8" class="empty-row">Không tìm thấy chương trình nào.</td>
            </tr>
            <tr v-else v-for="p in paginatedPromos" :key="p.id" :class="{ 'row-selected': selectedIds.includes(p.id) }">
              <td class="select-col">
                <input type="checkbox" :checked="selectedIds.includes(p.id)" @change="toggleItemSelection(p.id)" />
              </td>
              <td>
                <div class="promo-name-cell">
                  <div class="promo-icon" :style="{ background: p.iconBg }">
                    <span>{{ p.icon }}</span>
                  </div>
                  <div>
                    <p class="promo-name">{{ p.ten }}</p>

                    <p class="promo-code">{{ p.code }}</p>
                    <span :class="['category-badge', `category-${p.danhmuc || 'product'}`]">
                      {{ categoryLabel(p.danhmuc) }}
                    </span>
                  </div>
                </div>
              </td>
            <td>
              <span :class="['discount-tag', `discount-${p.loai || 'percent'}`]" :style="{ background: p.tagBg, color: p.tagColor }">{{ p.discount || discountLabel(p) }}</span>
            </td>
            <td class="date-cell">{{ (p.danhmuc === 'birthday' || p.danhmuc === 'event') ? '—' : (p.ngaybatdau || '—') }}</td>
            <td class="date-cell">{{ (p.danhmuc === 'birthday' || p.danhmuc === 'event') ? '—' : (p.ngayketthuc || '—') }}</td>
            <td>
              <span :class="['status-badge', p.congkhai == 1 ? 'status-running' : 'status-open']">
                {{ p.congkhai == 1 ? 'Công khai' : 'Có điều kiện' }}
              </span>
            </td>
            <td>
              <span :class="['status-badge', statusClass(p.actual_trangthai)]">
                {{ statusLabel(p.actual_trangthai) }}
              </span>
            </td>
            <td>
              <div class="actions">
                <button class="action-btn" @click="openEdit(p)" title="Sửa">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button class="action-btn action-delete" @click="deletePromo(p.id)" title="Xóa">
                  <svg viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

      <div class="pagination-row" v-if="currentTab === 'promotions'">
        <span class="page-info">Hiển thị 1-{{ filteredPromos.length }} trên <strong>{{ promos.length }}</strong> khuyến mãi</span>
        <div class="pagination">
          <button class="page-btn">‹</button>
          <button class="page-btn active">1</button>
          <button class="page-btn">›</button>
        </div>
      </div>

      <!-- HOLIDAY LIST (EVENTS TAB) -->
      <div class="holiday-list-wrapper" v-if="currentTab === 'events'">
        <div class="holiday-list">
          <div v-if="loading" class="empty-row">Đang tải chiến dịch...</div>
          <div v-else-if="upcomingHolidays.length === 0" class="empty-row">
            Chưa có chiến dịch hoặc sự kiện nào trong cơ sở dữ liệu.
          </div>
          <div class="holiday-item" v-for="(h, idx) in upcomingHolidays" :key="h.id" :class="{'next-holiday': idx === 0}">
            <div class="holiday-date-box" :class="{'multi-day': h.endDate}">
              <template v-if="h.endDate">
                <span class="holiday-day-range">{{ h.date.getDate() }}/{{ h.date.getMonth() + 1 }}</span>
                <span class="holiday-range-sep">—</span>
                <span class="holiday-day-range">{{ h.endDate.getDate() }}/{{ h.endDate.getMonth() + 1 }}</span>
              </template>
              <template v-else>
                <span class="holiday-day">{{ h.date.getDate() }}</span>
                <span class="holiday-month">Tháng {{ h.date.getMonth() + 1 }}</span>
              </template>
            </div>
            <div class="holiday-info">
              <p class="holiday-name">{{ eventDisplayName(h) }} {{ h.date.getFullYear() }}</p>
              <div class="holiday-meta" style="display: flex; gap: 16px; margin-top: 6px; font-size: 13px; color: #64748b;">
                <span style="display: flex; align-items: center; gap: 4px;">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                  {{ h.formattedDate }}
                </span>
                <span style="display: flex; align-items: center; gap: 4px; color: #059669; font-weight: 700; background: #dcfce7; padding: 2px 8px; border-radius: 6px; border: 1px dashed #86efac;">
                  Tên KM: {{ eventDisplayName(h) }}
                </span>
                <span style="display: flex; align-items: center; gap: 4px; color: #2563eb; font-weight: 700; background: #eff6ff; padding: 2px 8px; border-radius: 6px; border: 1px dashed #bfdbfe;">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                  Mã KM: {{ h.code }}
                </span>
              </div>
            </div>
            <div class="holiday-countdown">
              <span v-if="h.daysLeft === 0">Hôm nay</span>
              <span v-else-if="h.daysLeft > 0">Còn {{ h.daysLeft }} ngày</span>
              <span v-else>Đã qua</span>
            </div>
            <button
              type="button"
              class="auto-send-toggle"
              :class="{ enabled: h.autoSend }"
              :disabled="h.toggling"
              @click="toggleEventAutoSend(h)"
            >
              <span class="toggle-track"><span class="toggle-knob"></span></span>
              {{ h.toggling ? 'Đang lưu...' : (h.autoSend ? 'Tự động' : 'Không tự động') }}
            </button>
            <div class="holiday-actions actions">
              <button class="action-btn" @click="openEdit(h.promotion)" title="Sửa chiến dịch">
                <svg viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <button class="action-btn action-delete" @click="deletePromo(h.id)" title="Xóa chiến dịch">
                <svg viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

    <!-- BOTTOM CARDS -->
    <div class="bottom-row">
      <div class="bottom-card">
        <div class="bottom-card-header">
          <h3>Chiến dịch hiệu quả nhất</h3>
          <button class="icon-btn-sm"><svg viewBox="0 0 24 24" fill="none"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></button>
        </div>
        <div class="rank-list">
          <div class="rank-item" v-for="(r, i) in topPromos" :key="r.id">
            <span class="rank-num">#{{ i + 1 }}</span>
            <div class="rank-bar-wrap">
              <p class="rank-name">{{ r.ten }}</p>
              <div class="rank-bar"><div class="rank-fill" :style="{ width: r.roi + '%', background: i === 0 ? '#4f46e5' : '#e2e8f0' }"></div></div>
            </div>
            <span class="rank-roi" :style="{ color: i === 0 ? '#2563eb' : '#3b82f6' }">+{{ r.roi }}% ROI</span>
          </div>
        </div>
      </div>
    </div>

    </template><!-- end list view -->

    <template v-if="currentView === 'promo-form'">
      <!-- Inline form header -->
      <div class="inline-form-header">
        <button class="back-btn" @click="closeModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16">
            <path d="M15 18l-6-6 6-6" />
          </svg>
          Quay lại danh sách
        </button>
        <h1>{{ form.danhmuc === 'event' ? (isEdit ? 'Chỉnh sửa chiến dịch' : 'Tạo chiến dịch mới') : (isEdit ? 'Chỉnh sửa khuyến mãi' : 'Tạo khuyến mãi mới') }}</h1>
        <p>{{ isEdit ? 'Cập nhật thông tin và lưu thay đổi vào cơ sở dữ liệu' : 'Điền đầy đủ thông tin để tạo chương trình mới' }}</p>
      </div>

      <form class="inline-form-body" @submit.prevent="savePromo">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Loại Voucher <span class="req">*</span></label>
            <select class="form-input" v-model="form.danhmuc" @change="onCategoryChange" :disabled="isEdit">
              <option value="product">Giảm giá sản phẩm</option>
              <option value="birthday">Mã Sinh nhật</option>
              <option value="freeship">Miễn phí vận chuyển (Freeship)</option>
              <option value="event">Sự kiện & Ngày lễ</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Tên Voucher <span class="req">*</span></label>
            <input class="form-input" :class="{ err: errors.ten }" v-model="form.ten" placeholder="VD: Tết 2026 Sale"
              @input="autoCode" />
            <p class="err-msg" v-if="errors.ten">{{ errors.ten }}</p>
          </div>
        </div>
        <div class="birthday-private-note" v-if="form.danhmuc === 'birthday'">
          Mã sinh nhật là ưu đãi riêng tư: chỉ xuất hiện trong phân hệ Gửi mã sinh nhật và chỉ khách được cấp mã mới sử dụng được.
        </div>

        <div class="form-group">
          <label class="form-label">Mã Voucher *</label>
          <div class="code-input-row">
            <input class="form-input mono" :class="{ err: errors.code }" v-model="form.code" :placeholder="form.danhmuc === 'event' ? 'VD: QUOCKHANH' : 'VD: TET-2026'"
              style="text-transform:uppercase" :readonly="form.danhmuc === 'birthday'" />
            <button type="button" class="btn-gen-code" @click="generateRandomCode"
              v-if="form.danhmuc !== 'event'"
              :disabled="form.danhmuc === 'birthday'">Tạo ngẫu nhiên</button>
          </div>
          <p class="err-msg" v-if="errors.code">{{ errors.code }}</p>
          <p class="form-hint" v-if="form.danhmuc === 'event'">Mã KM là chữ viết hoa không dấu, ví dụ QUOCKHANH.</p>
          <p class="form-hint" v-else>Mã sẽ tự động sinh khi bạn gõ tên. Bạn cũng có thể bấm nút Tạo ngẫu nhiên.</p>
        </div>
        <div class="form-group" v-if="form.danhmuc === 'event'">
          <label class="form-label">Ngày kích hoạt hằng năm *</label>
          <input
            class="form-input"
            :class="{ err: errors.ngay_su_kien }"
            type="date"
            :value="eventDateInputValue(form.ngay_su_kien)"
            @input="form.ngay_su_kien = inputDateToEventDate($event.target.value)"
          />
          <p class="err-msg" v-if="errors.ngay_su_kien">{{ errors.ngay_su_kien }}</p>
        </div>

        <div class="form-row" v-if="form.danhmuc !== 'freeship'">
          <div class="form-group">
            <label class="form-label">Loại ưu đãi <span class="req">*</span></label>
            <select class="form-input" v-model="form.loai" :disabled="form.danhmuc === 'birthday'">
              <option value="percent">Giảm %</option>
              <option value="fixed">Giảm theo giá tiền</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Giá trị <span class="req">*</span></label>
            <div class="input-suffix-wrap">
              <input class="form-input" :class="{ err: errors.giatri }" 
                type="text" 
                :value="form.loai === 'percent' || form.loai === 'maxprice' ? form.giatri : formatVND(form.giatri)"
                @input="form.giatri = (form.loai === 'percent' || form.loai === 'maxprice') ? $event.target.value : parseVND($event.target.value)"
                placeholder="50" />
              <span class="input-suffix">{{ form.loai === 'percent' ? '%' : 'VNĐ' }}</span>
            </div>
            <p class="err-msg" v-if="errors.giatri">{{ errors.giatri }}</p>
          </div>
        </div>

        <!-- Hình thức hiển thị / phát hành -->
        <div class="form-row" v-if="form.danhmuc !== 'birthday'">
          <div class="form-group" style="flex: 1;">
            <label class="form-label">Hình thức hiển thị / phát hành <span class="req">*</span></label>
            <div style="display: flex; gap: 1rem; align-items: center; margin-top: 8px;">
              <label style="cursor: pointer; display: flex; align-items: center; gap: 6px;">
                <input type="radio" :value="1" v-model="form.congkhai" />
                Công khai (Hiện trên web)
              </label>
              <label style="cursor: pointer; display: flex; align-items: center; gap: 6px;">
                <input type="radio" :value="0" v-model="form.congkhai" />
                Tặng khi đủ điều kiện
              </label>
            </div>
            <p class="form-hint">Tặng có điều kiện sẽ tự động tặng cho khách khi đặt hàng thành công.</p>
          </div>
        </div>

        <div class="form-row condition-row" style="background: #fffbfa; border: 1px dashed #f87171;"
          v-if="form.danhmuc !== 'birthday' && form.congkhai === 0">
          <div class="form-group">
            <label class="form-label">Mức đơn hàng để tặng (VNĐ)</label>
            <div class="input-suffix-wrap">
              <input class="form-input" type="text" :value="formatVND(form.dieu_kien_tang)"
                @input="form.dieu_kien_tang = parseVND($event.target.value)" placeholder="VD: 1.000.000" />
              <span class="input-suffix">đ</span>
            </div>
            <p class="form-hint">Tổng tiền phải thanh toán của đơn hàng (sau giảm giá) đạt mức này sẽ được tặng voucher.
            </p>
          </div>
          <div class="form-group">
            <label class="form-label">Giới hạn số lượng phát</label>
            <input class="form-input" v-model.number="form.so_luong_phat" type="number" min="1"
              placeholder="Để trống = Không giới hạn" />
            <p class="form-hint">Tổng số lượng khách được tặng mã này.</p>
          </div>
        </div>

        <!-- Điều kiện đơn hàng (để sử dụng voucher) -->
        <div class="form-row condition-row" :class="{ 'freeship-row': form.danhmuc === 'freeship' }"
          v-if="form.danhmuc === 'product' || form.danhmuc === 'freeship'">
          <div class="form-group" v-if="form.danhmuc === 'product'">
            <label class="form-label">
              <span class="condition-badge">🎯 Điều kiện đơn hàng</span>
            </label>
            <select class="form-input condition-select" v-model="form.loai_dieu_kien">
              <option value=">=">≥ Tạm tính từ (lớn hơn hoặc bằng)</option>
              <option value=">">＞ Tạm tính hơn (lớn hơn)</option>
              <option value="=">＝ Tạm tính bằng đúng</option>
            </select>
          </div>
          <div class="form-group" v-if="form.danhmuc === 'freeship'">
            <label class="form-label">
              <span class="condition-badge freeship-badge">🚚 Điều kiện miễn phí ship</span>
            </label>
            <p class="form-hint" style="margin-bottom:4px">Khách hàng phải đạt tạm tính tối thiểu mới dùng được mã
              freeship này.</p>
          </div>
          <div class="form-group">
            <label class="form-label">
              {{ form.danhmuc === 'freeship' ? 'Đơn hàng tối thiểu (VNĐ)' : 'Giá trị điều kiện (VNĐ)' }}
            </label>
            <div class="input-suffix-wrap">
              <input class="form-input condition-input"
                :class="{ err: errors.dieu_kien, 'freeship-input': form.danhmuc === 'freeship' }" type="text"
                :value="formatVND(form.dieu_kien)" @input="form.dieu_kien = parseVND($event.target.value)"
                :placeholder="form.danhmuc === 'freeship' ? 'VD: 300.000' : 'VD: 500.000'" />
              <span class="input-suffix">đ</span>
            </div>
            <p class="err-msg" v-if="errors.dieu_kien">{{ errors.dieu_kien }}</p>
            <p class="form-hint">
              {{ form.danhmuc === 'freeship'
                ? 'Để trống = freeship cho mọi đơn hàng. Nhập số để giới hạn điều kiện tối thiểu.'
                : 'Để trống nếu không cần điều kiện tạm tính.' }}
            </p>
          </div>
        </div>

        <!-- Ngày bắt đầu & Kết thúc -->
        <div class="form-row" v-if="form.danhmuc !== 'birthday' && form.danhmuc !== 'event'">
          <div class="form-group">
            <label class="form-label">Ngày bắt đầu</label>
            <input class="form-input" type="date" v-model="form.ngaybatdau" />
          </div>
          <div class="form-group">
            <label class="form-label">Ngày kết thúc</label>
            <input class="form-input" type="date" v-model="form.ngayketthuc" />
          </div>
        </div>
        <div class="form-group" v-if="form.danhmuc === 'birthday'">
          <div class="birthday-status-info">
            <span class="birthday-icon">BD</span>
            <span>Mã sinh nhật sẽ <strong>luôn mở</strong> và không có thời hạn.</span>
          </div>
        </div>
        <div class="form-group" v-if="form.danhmuc === 'event'">
          <div class="birthday-status-info" style="background: #e0f2fe; border-color: #bae6fd;">
            <span class="birthday-icon" style="background: #3b82f6;">EV</span>
            <span>Sự kiện sẽ tự động <strong>kích hoạt lặp lại hằng năm</strong> theo ngày kích hoạt đã nhập.</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Mô tả</label>
          <textarea class="form-input form-textarea" v-model="form.mota" rows="4"
            placeholder="Mô tả ngắn về chương trình khuyến mãi..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Biểu tượng</label>
          <div class="icon-picker">
            <button v-for="ic in iconOptions" :key="ic.icon" type="button" class="icon-option"
              :class="{ 'icon-option-active': form.icon === ic.icon }" :style="{ background: ic.bg }"
              @click="form.icon = ic.icon; form.iconBg = ic.bg">{{ ic.icon }}</button>
          </div>
        </div>

        <!-- Inline footer actions -->
        <div class="inline-form-footer">
          <button type="button" class="btn-cancel" @click="closeModal">Hủy</button>
          <button type="submit" class="btn-save" :disabled="saving">
            <svg v-if="saving" class="spin" viewBox="0 0 24 24" fill="none">
              <path d="M21 12a9 9 0 1 1-6.219-8.56" />
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
              <polyline points="17 21 17 13 7 13 7 21" />
              <polyline points="7 3 7 8 15 8" />
            </svg>
            {{ saving ? 'Đang lưu...' : (isEdit ? 'Lưu thay đổi' : 'Tạo khuyến mãi') }}
          </button>
        </div>

      </form><!-- end inline-form-body -->
    </template><!-- end promo-form -->

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'
import { registerOfflineForm } from '@/services/offlineSync'
import BulkDeleteToolbar from './ThanhXoaHangLoat.vue'
import { useAdminBulkDelete } from '@/services/adminBulkDelete'
import PhanTrangAdmin from './PhanTrangAdmin.vue'

const searchQuery = ref('')
const currentView = ref('list') // 'list' | 'promo-form'
const currentTab = ref('promotions') // 'promotions' | 'events'
const isEdit = ref(false)
const editId = ref(null)
const loading = ref(false)
const saving = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)

function parseEventDate(eventDate) {
  const value = String(eventDate || '').trim()
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  // Mã lặp hằng năm: DD-MM.
  const annualMatch = value.match(/^(\d{2})-(\d{2})$/)
  if (annualMatch) {
    const day = Number(annualMatch[1])
    const month = Number(annualMatch[2])
    let date = new Date(today.getFullYear(), month - 1, day)
    if (date < today) date = new Date(today.getFullYear() + 1, month - 1, day)
    if (date.getDate() === day && date.getMonth() === month - 1) return date
  }

  // Hỗ trợ dữ liệu cũ dạng DDMMYYYY.
  const fixedMatch = value.match(/^(\d{2})(\d{2})(\d{4})$/)
  if (fixedMatch) {
    const day = Number(fixedMatch[1])
    const month = Number(fixedMatch[2])
    const year = Number(fixedMatch[3])
    const date = new Date(year, month - 1, day)
    if (date.getDate() === day && date.getMonth() === month - 1) return date
  }

  return null
}

function eventDisplayName(event) {
  const rawCode = String(event?.code || '').trim()
  const source = /^\d{2}-\d{2}$/.test(rawCode) ? event?.name : (rawCode || event?.name)
  const key = String(source || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/Đ/g, 'D')
    .replace(/đ/g, 'D')
    .replace(/\d{2}-\d{2}$/g, '')
    .replace(/[^A-Z0-9]/gi, '')
    .toUpperCase()

  return {
    TETDUONGLICH: 'Tết Dương Lịch',
    TETNGUYENDAN: 'Tết Nguyên Đán',
    QUOCTEPHUNU: 'Quốc Tế Phụ Nữ',
    GIOTOHUNGVUONG: 'Giỗ Tổ Hùng Vương',
    GIAIPHONGMIENNAM: 'Giải Phóng Miền Nam',
    QUOCTELAODONG: 'Quốc Tế Lao Động',
    QUOCTETHIEUNHI: 'Quốc Tế Thiếu Nhi',
    QUOCKHANH: 'Quốc Khánh',
    TETTRUNGTHU: 'Tết Trung Thu',
    PHUNUVIETNAM: 'Phụ Nữ Việt Nam',
    NHAGIAOVIETNAM: 'Nhà Giáo Việt Nam',
    GIANGSINH: 'Giáng Sinh',
  }[key] || String(event?.name || 'Sự Kiện')
}

const upcomingHolidays = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return promos.value
    .filter(p => p.danhmuc === 'event')
    .map(p => {
      const date = parseEventDate(p.ngay_su_kien)
      const daysLeft = date ? Math.ceil((date - today) / (1000 * 60 * 60 * 24)) : -1
      return {
        id: p.id,
        name: p.ten,
        code: p.code,
        date: date || today,
        endDate: null,
        daysLeft,
        formattedDate: date ? formatDate(date) : 'Ngày chưa hợp lệ',
        autoSend: Boolean(p.tu_dong_gui),
        toggling: false,
        promotion: p,
      }
    })
    .sort((a, b) => {
      if (a.daysLeft < 0 && b.daysLeft >= 0) return 1
      if (b.daysLeft < 0 && a.daysLeft >= 0) return -1
      return a.date - b.date
    })
})

const nextHoliday = computed(() => upcomingHolidays.value.length > 0 ? upcomingHolidays.value[0] : null)

function goToEvents() {
  currentTab.value = 'events'
  // Scroll xuống khu vực holiday list sau khi Vue render
  setTimeout(() => {
    const el = document.querySelector('.holiday-list-wrapper')
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    // Highlight sự kiện đầu tiên (next-holiday)
    const firstItem = document.querySelector('.holiday-item.next-holiday')
    if (firstItem) {
      firstItem.classList.add('holiday-highlight-pulse')
      setTimeout(() => firstItem.classList.remove('holiday-highlight-pulse'), 2000)
    }
  }, 80)
}

// ── Toast ──────────────────────────────────────
const toast = ref({ show: false, msg: '', type: 'success' })
const showToast = (msg, type = 'success') => {
  toast.value = { show: true, msg, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

async function toggleEventAutoSend(holiday) {
  if (holiday.toggling) return
  holiday.toggling = true
  const nextValue = !holiday.autoSend

  try {
    await api.patch(`/admin/promotions/${holiday.id}/auto-send`, { tu_dong_gui: nextValue })
    holiday.autoSend = nextValue
    holiday.promotion.tu_dong_gui = nextValue
    const promo = promos.value.find(item => item.id === holiday.id)
    if (promo) promo.tu_dong_gui = nextValue
    swal.success(nextValue ? 'Đã bật tự động gửi Gmail!' : 'Đã tắt tự động gửi Gmail!')
  } catch (error) {
    console.error(error)
    swal.error('Lỗi', 'Không thể cập nhật chế độ gửi Gmail.')
  } finally {
    holiday.toggling = false
  }
}

const statusOptions = [
  { value: 'running', label: 'Đang chạy', color: '#2563eb', bg: '#f0fdf4' },
  { value: 'open', label: 'Luôn mở', color: '#2563eb', bg: '#eff6ff' },
]

const iconOptions = [
  { icon: 'SALE', bg: '#fef3c7' },
  { icon: 'BAG', bg: '#fce7f3' },
  { icon: 'EDU', bg: '#dbeafe' },
  { icon: 'FAST', bg: '#fef9c3' },
  { icon: 'GIFT', bg: '#d1fae5' },
  { icon: 'HOT', bg: '#fee2e2' },
  { icon: 'VIP', bg: '#ede9fe' },
  { icon: 'NEW', bg: '#e0f2fe' },
]

const defaultForm = () => ({
  ten: '', danhmuc: 'product', code: '', ngay_su_kien: '', loai: 'percent', giatri: '',
  ngaybatdau: '', ngayketthuc: '', trangthai: 'running',
  mota: '', icon: 'SALE', iconBg: '#fef3c7',
  loai_dieu_kien: '>=', dieu_kien: '',
  congkhai: 1, dieu_kien_tang: '', so_luong_phat: '', tu_dong_gui: true
})

const form = ref(defaultForm())
registerOfflineForm(form, 'quan-ly-khuyen-mai')
const errors = ref({})
const promos = ref([])

// ================= FETCH DATA =================
const fetchPromos = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/promotions')
    const now = new Date()
    const rawList = Array.isArray(res.data) ? res.data : (Array.isArray(res.data?.data) ? res.data.data : [])
    promos.value = rawList.map(p => {
      let actualStatus = p.trangthai
      
      if (actualStatus === 'running' && p.ngayketthuc) {
        const endDate = new Date(p.ngayketthuc)
        endDate.setHours(23, 59, 59, 999)
        if (endDate < now) {
          actualStatus = 'expired'
        }
      }
      return {
        ...p,
        actual_trangthai: actualStatus,
        ngaybatdau: formatDate(p.ngaybatdau),
        ngayketthuc: formatDate(p.ngayketthuc),
        discount: discountLabel(p),
        ...tagColors(p.loai),
        icon: '🏮',
        iconBg: '#fef3c7',
        roi: 20
      }
    })
  } catch (err) {
    swal.error('Lỗi', 'Lỗi tải danh sách khuyến mãi!')
    console.error(err)
  } finally {
    loading.value = false
  }
}

const syncSuccessHandler = () => {
  fetchPromos()
}

onMounted(() => {
  fetchPromos()
  window.addEventListener('offline-sync-success', syncSuccessHandler)
})

onBeforeUnmount(() => {
  window.removeEventListener('offline-sync-success', syncSuccessHandler)
})

// ================= COMPUTED =================
const filteredPromos = computed(() => {
  let list = promos.value
  if (currentTab.value === 'promotions') {
    list = list.filter(p => p.danhmuc !== 'event')
  } else {
    list = list.filter(p => p.danhmuc === 'event')
  }
  
  if (!searchQuery.value) return list
  const q = searchQuery.value.toLowerCase()
  return list.filter(p =>
    p.ten.toLowerCase().includes(q) ||
    p.code.toLowerCase().includes(q)
  )
})

const totalPages = computed(() => Math.ceil(filteredPromos.value.length / pageSize.value) || 1)

const paginatedPromos = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredPromos.value.slice(start, start + pageSize.value)
})

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
  items: promos,
  filteredItems: filteredPromos,
  getId: item => item.id,
  endpoint: id => `/admin/promotions/${id}`,
  entityLabel: 'khuyến mãi',
  fetchItems: fetchPromos,
})

const activeCount = computed(() =>
  promos.value.filter(p => p.actual_trangthai === 'running' || p.actual_trangthai === 'open').length
)

const topPromos = computed(() =>
  [...promos.value].sort((a, b) => b.roi - a.roi).slice(0, 2)
)

// ================= HELPERS =================
function statusClass(s) {
  return { running: 'status-running', expired: 'status-expired', open: 'status-open' }[s] || ''
}

function statusLabel(s) {
  return { running: '● Đang chạy', expired: '◌ Hết hạn', open: '● Luôn mở' }[s] || s
}

function categoryLabel(category) {
  return {
    birthday: 'Sinh nhật',
    freeship: 'Freeship',
    product: 'Sản phẩm',
  }[category] || 'Khác'
}

function autoCode() {
  if (!isEdit.value && form.value.danhmuc !== 'birthday') {
    const base = form.value.ten
      .toUpperCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/Đ/gi, 'D')
      .replace(/[^A-Z0-9\s]/g, '')
      .trim().replace(/\s+/g, '-')
      .slice(0, 15)

    if (base) form.value.code = form.value.danhmuc === 'event' ? base.replace(/-/g, '') : base + '-' + Math.floor(1000 + Math.random() * 9000)
  }
}

function generateRandomCode() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
  let result = ''
  for (let i = 0; i < 8; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  form.value.code = result
}

function onCategoryChange() {
  if (form.value.danhmuc === 'freeship') {
    form.value.loai = 'percent'
    form.value.giatri = 100
    form.value.trangthai = 'running'
  } else if (form.value.danhmuc === 'birthday') {
    form.value.loai = 'percent'
    form.value.code = 'BIRTHDAY'
    form.value.trangthai = 'open'
    form.value.ngaybatdau = ''
    form.value.ngayketthuc = ''
  } else if (form.value.danhmuc === 'event') {
    form.value.trangthai = 'open'
    form.value.ngaybatdau = ''
    form.value.ngayketthuc = ''
    form.value.code = ''
    form.value.ngay_su_kien = ''
  } else {
    form.value.trangthai = 'running'
  }
}

function discountLabel(f) {
  if (f.loai === 'percent') return `Giảm ${f.giatri}%`
  if (f.loai === 'fixed') return `Cố định ${f.giatri}đ`
  if (f.loai === 'freeship') return `Freeship ${f.giatri}đ`
  return ''
}

function tagColors(type) {
  return {
    percent:  { tagBg: '#fef3c7', tagColor: '#92400e' },
    fixed:    { tagBg: '#dbeafe', tagColor: '#1e40af' },
    maxprice: { tagBg: '#fef9c3', tagColor: '#854d0e' },
    freeship: { tagBg: '#dcfce7', tagColor: '#166534' },
  }[type] || {}
}

function formatDate(d) {
  if (!d) return ''
  const date = new Date(d)
  const dd = String(date.getDate()).padStart(2, '0')
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const yyyy = date.getFullYear()
  return `${dd}/${mm}/${yyyy}`
}

function eventDateInputValue(eventDate) {
  const match = String(eventDate || '').match(/^(\d{2})-(\d{2})$/)
  if (!match) return ''
  const today = new Date()
  const eventThisYear = new Date(today.getFullYear(), Number(match[2]) - 1, Number(match[1]))
  eventThisYear.setHours(23, 59, 59, 999)
  const year = eventThisYear < today ? today.getFullYear() + 1 : today.getFullYear()
  return `${year}-${match[2]}-${match[1]}`
}

function inputDateToEventDate(inputDate) {
  const match = String(inputDate || '').match(/^\d{4}-(\d{2})-(\d{2})$/)
  return match ? `${match[2]}-${match[1]}` : ''
}

function formatVND(val) {
  if (!val && val !== 0) return '';
  const numStr = String(val).replace(/\D/g, '');
  if (!numStr) return '';
  return new Intl.NumberFormat('vi-VN').format(Number(numStr));
}

function parseVND(val) {
  if (!val) return '';
  return Number(String(val).replace(/\D/g, ''));
}

// ================= VALIDATE =================
function validate() {
  errors.value = {}
  if (!form.value.ten.trim()) errors.value.ten = 'Tên không được để trống.'
  if (!form.value.code.trim()) errors.value.code = 'Mã không được để trống.'
  if (form.value.danhmuc === 'event') {
    if (!/^[A-Z0-9_-]+$/.test(form.value.code.trim().toUpperCase())) errors.value.code = 'Mã phải là chữ in hoa không dấu.'
    const match = form.value.ngay_su_kien.trim().match(/^(\d{2})-(\d{2})$/)
    const day = match ? Number(match[1]) : 0
    const month = match ? Number(match[2]) : 0
    const validDate = match && month >= 1 && month <= 12 && day >= 1 && day <= new Date(2000, month, 0).getDate()
    if (!validDate) errors.value.ngay_su_kien = 'Nhập ngày hợp lệ theo định dạng DD-MM, ví dụ 02-09.'
  }
  if (form.value.giatri === '' || form.value.giatri === null)
    errors.value.giatri = 'Vui lòng nhập giá trị.'
  return Object.keys(errors.value).length === 0
}

// ================= CRUD =================
function openCreate() {
  isEdit.value = false
  editId.value = null
  form.value = defaultForm()
  if (currentTab.value === 'events') {
    form.value.danhmuc = 'event'
    form.value.trangthai = 'open'
  }
  errors.value = {}
  currentView.value = 'promo-form'
}

function openCreateFromHoliday(h) {
  isEdit.value = false
  editId.value = null
  form.value = defaultForm()
  form.value.danhmuc = 'event'
  form.value.trangthai = 'open'
  
  // Tên sự kiện: Loại bỏ năm ở cuối (VD: "Quốc khánh 2026" -> "Quốc khánh")
  form.value.ten = h.name.replace(/\s\d{4}$/, '')
  
  // Mã sự kiện: Ngày sự kiện (DD-MM)
  const dd = String(h.date.getDate()).padStart(2, '0')
  const mm = String(h.date.getMonth() + 1).padStart(2, '0')
  form.value.ngay_su_kien = `${dd}-${mm}`
  autoCode()
  
  errors.value = {}
  currentView.value = 'promo-form'
}

function openEdit(p) {
  isEdit.value = true
  editId.value = p.id

  // Chuyển dd/mm/yyyy → yyyy-mm-dd cho input[type=date]
  const toInputDate = (str) => {
    if (!str) return ''
    const parts = str.split('/')
    if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`
    return str
  }

  form.value = {
    ...p,
    danhmuc: p.danhmuc || 'product',
    loai: p.danhmuc === 'birthday' ? 'percent' : (p.loai || 'percent'),
    ngaybatdau: toInputDate(p.ngaybatdau),
    ngayketthuc: toInputDate(p.ngayketthuc),
    loai_dieu_kien: p.loai_dieu_kien || '>=',
    dieu_kien: p.dieu_kien || '',
    congkhai: p.congkhai !== undefined ? Number(p.congkhai) : 1,
    dieu_kien_tang: p.dieu_kien_tang || '',
    so_luong_phat: p.so_luong_phat || ''
  }
  errors.value = {}
  currentView.value = 'promo-form'
}

function closeModal() {
  currentView.value = 'list'
}

async function savePromo() {
  if (!validate()) return

  saving.value = true

  // Birthday và event luôn mở, freeship/product dùng ngày
  const isBirthday = form.value.danhmuc === 'birthday'
  const isEvent = form.value.danhmuc === 'event'
  const data = {
    ten: form.value.ten,
    danhmuc: form.value.danhmuc,
    code: form.value.code.toUpperCase(),
    ngay_su_kien: isEvent ? form.value.ngay_su_kien : null,
    tu_dong_gui: isEvent ? Boolean(form.value.tu_dong_gui) : false,
    loai: isBirthday ? 'percent' : form.value.loai,
    giatri: form.value.giatri,
    ngaybatdau: (isBirthday || isEvent) ? null : (form.value.ngaybatdau || null),
    ngayketthuc: (isBirthday || isEvent) ? null : (form.value.ngayketthuc || null),
    trangthai: (isBirthday || isEvent) ? 'open' : 'running',
    mota: form.value.mota,
    loai_dieu_kien: form.value.danhmuc === 'product' ? (form.value.loai_dieu_kien || '>=') : null,
    dieu_kien: (form.value.danhmuc === 'product' || form.value.danhmuc === 'freeship') ? (form.value.dieu_kien || null) : null,
    congkhai: form.value.congkhai,
    dieu_kien_tang: form.value.congkhai === 0 ? (form.value.dieu_kien_tang || null) : null,
    so_luong_phat: form.value.congkhai === 0 ? (form.value.so_luong_phat || null) : null,
  }

  try {
    if (isEdit.value) {
      // PUT /api/admin/promotions/{id} — cần token admin
      await api.put(`/admin/promotions/${editId.value}`, data)
      swal.success(isEvent ? 'Cập nhật chiến dịch thành công!' : 'Cập nhật khuyến mãi thành công!')
    } else {
      // POST /api/admin/promotions — cần token admin
      await api.post('/admin/promotions', data)
      swal.success(isEvent ? 'Tạo chiến dịch thành công!' : 'Tạo khuyến mãi thành công!')
    }

    await fetchPromos()
    closeModal()

  } catch (err) {
    const msg = err.response?.data?.message || 'Lỗi khi lưu khuyến mãi!'
    swal.error('Lỗi', msg)
    console.error(err.response?.data)
  } finally {
    saving.value = false
  }
}

async function deletePromo(id) {
  const isEvent = currentTab.value === 'events'
  const isConfirmed = await swal.confirm(
    'Xác nhận xóa',
    isEvent ? 'Bạn chắc chắn muốn xóa chiến dịch này khỏi cơ sở dữ liệu?' : 'Bạn chắc chắn muốn xóa khuyến mãi này?'
  )
  if (!isConfirmed) return

  try {
    // DELETE /api/admin/promotions/{id} — cần token admin
    await api.delete(`/admin/promotions/${id}`)
    swal.success(isEvent ? 'Xóa chiến dịch thành công!' : 'Xóa khuyến mãi thành công!')
    await fetchPromos()
  } catch (err) {
    swal.error('Lỗi', 'Lỗi khi xóa khuyến mãi!')
    console.error(err)
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.page {
  padding: 20px 24px;
  background: #f0f4ff;
  min-height: 100vh;
  font-family: 'Be Vietnam Pro', 'Segoe UI', sans-serif;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

/* TABS */
.page-tabs-wrap {
  border-bottom: 1px solid #e2e8f0;
  margin-bottom: 4px;
}

.page-tabs {
  display: flex;
  gap: 30px;
}

.page-tab {
  background: none;
  border: none;
  padding: 0 0 12px 0;
  font-size: 15px;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  position: relative;
  transition: color 0.2s;
}

.page-tab:hover {
  color: #1e293b;
}

.page-tab.active {
  color: #2563eb;
}

.page-tab.active::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 0;
  width: 100%;
  height: 2px;
  background: #2563eb;
  border-radius: 2px 2px 0 0;
}

/* TOPBAR */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.search-box {
  display: flex;
  align-items: center;
  background: #fff;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  padding: 0 12px;
  width: 280px;
  height: 38px;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.search-box:focus-within {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
}

.search-box svg {
  width: 15px;
  height: 15px;
  stroke: #64748b;
  stroke-width: 2;
  fill: none;
  flex-shrink: 0;
  margin-right: 8px;
}

.search-box input {
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
  font-size: 13px;
  color: #0f172a;
  background: transparent !important;
  width: 100%;
  height: 100%;
  padding: 0 !important;
  border-radius: 0 !important;
}

.search-box input::placeholder {
  color: #94a3b8;
}

.topbar-right {
  display: none !important;
}

.icon-btn {
  position: relative;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.icon-btn svg {
  width: 16px;
  height: 16px;
  stroke: #64748b;
  stroke-width: 1.8;
  fill: none;
}

.notif-dot {
  position: absolute;
  top: 7px;
  right: 7px;
  width: 7px;
  height: 7px;
  background: #ef4444;
  border-radius: 50%;
  border: 1.5px solid #fff;
}

.admin-info {
  display: flex;
  align-items: center;
  gap: 9px;
}

.admin-name {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  text-align: right;
}

.admin-role {
  font-size: 11px;
  color: #94a3b8;
  text-align: right;
}

.admin-avatar {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #3b82f6, #3b82f6);
  border-radius: 50%;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* TOAST */
.toast {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 99999;
  padding: 12px 20px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.toast.success {
  background: #0f172a;
  color: #fff;
}

.toast.success svg {
  stroke: #4ade80;
}

.toast.error {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.toast.error svg {
  stroke: #dc2626;
}

.toast svg {
  width: 18px;
  height: 18px;
  stroke-width: 2.5;
  fill: none;
  flex-shrink: 0;
}

.toast-enter-active {
  transition: all 0.3s cubic-bezier(0.34, 1.4, 0.64, 1);
}

.toast-leave-active {
  transition: all 0.2s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(-12px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* STATS */
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(220px, 1fr));
  gap: 20px;
}

.stat-card {
  min-height: 136px;
  background: #fff;
  border-radius: 16px;
  padding: 26px 28px;
  border: 1px solid transparent;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12);
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 10px;
  position: relative;
  overflow: hidden;
}

.stat-card.stat-active {
  background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
  border: none;
}

.stat-card.stat-active::after,
.stat-card.stat-budget::after,
.stat-card-gradient::after {
  content: '';
  position: absolute;
  width: 150px;
  height: 150px;
  border-radius: 999px;
  top: -54px;
  right: -28px;
  background: rgba(255, 255, 255, 0.13);
  pointer-events: none;
}

.stat-card.stat-active .stat-label {
  color: rgba(255, 255, 255, 0.88);
  position: relative;
  z-index: 1;
}

.stat-card.stat-active .stat-value {
  color: #fff;
  position: relative;
  z-index: 1;
}

.stat-card.stat-active .stat-sub.green {
  align-self: flex-start;
  background: transparent !important;
  border-radius: 0 !important;
  color: #ffffff !important;
  font-size: 14px !important;
  font-weight: 800 !important;
  line-height: 1.35 !important;
  padding: 0 !important;
  position: relative;
  text-shadow: 0 1px 2px rgba(15, 23, 42, 0.18);
  white-space: normal;
  z-index: 1;
}

.stat-card.stat-budget {
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
  border: none;
}

.stat-card.stat-budget .stat-label {
  color: rgba(255, 255, 255, 0.88);
}

.stat-card.stat-budget .stat-value {
  color: #fff;
}

.stat-card.stat-budget .stat-unit {
  color: #dbeafe;
}

.stat-card.stat-budget .stat-bar {
  background: rgba(255, 255, 255, 0.22);
}

.stat-card.stat-budget .stat-bar-fill {
  background: linear-gradient(90deg, #bfdbfe, #ffffff);
}

.stat-label {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.03em;
  color: rgba(255, 255, 255, 0.88);
  text-transform: capitalize;
}

.stat-value {
  font-size: 34px;
  line-height: 1;
  font-weight: 800;
  color: #0f172a;
}

.stat-unit {
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
}

.stat-sub {
  font-size: 12px;
  color: #64748b;
}

.stat-sub.green {
  color: #2563eb;
  font-weight: 600;
}

.stat-bar {
  height: 5px;
  background: #dfe3e7;
  border-radius: 99px;
  overflow: hidden;
  margin-top: 4px;
}

.stat-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #2563eb, #3b82f6);
  border-radius: 99px;
}

.stat-card-gradient {
  background: linear-gradient(135deg, #0f2747 0%, #1e3a5f 55%, #0f172a 100%);
  border: none;
  justify-content: center;
}

.stat-card-tag {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.03em;
  color: rgba(255, 255, 255, 0.7);
  text-transform: capitalize;
}

.stat-card-desc {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.6;
}

.stat-card-desc strong {
  color: #fff;
}

.stat-card-btn {
  align-self: flex-start;
  padding: 7px 16px;
  border-radius: 20px;
  border: none;
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  backdrop-filter: blur(6px);
  transition: background 0.2s;
}

.stat-card-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.stat-card-subdesc {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.7);
  margin-top: 2px;
}

.stat-code-badge {
  display: inline-block;
  background: rgba(255, 255, 255, 0.18);
  color: #fff;
  font-family: 'Courier New', monospace;
  font-weight: 700;
  font-size: 12px;
  padding: 1px 8px;
  border-radius: 6px;
  letter-spacing: 0.06em;
  border: 1px solid rgba(255, 255, 255, 0.25);
}

/* LIST HEADER */
.list-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
}

.list-title {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
}

.list-sub {
  font-size: 12.5px;
  color: #64748b;
  margin-top: 3px;
}

.list-actions {
  display: flex;
  gap: 10px;
}

.btn-filter {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 9px 16px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
}

.btn-filter svg {
  width: 14px;
  height: 14px;
  stroke: #475569;
  stroke-width: 2;
  fill: none;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
  transition: transform 0.15s;
}

.btn-primary:hover {
  transform: translateY(-1px);
}

.btn-primary svg {
  width: 14px;
  height: 14px;
  stroke: #fff;
  stroke-width: 2.5;
  fill: none;
}

/* TABLE */
.table-card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e8edf5;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
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

.promo-name-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.promo-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .03em;
  flex-shrink: 0;
}

.promo-name {
  font-size: 13.5px;
  font-weight: 700;
  color: #1e293b;
}

.promo-code {
  font-size: 11px;
  color: #94a3b8;
  font-family: monospace;
  margin-top: 2px;
}

.discount-tag {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 700;
}

.date-cell {
  font-size: 13px;
  color: #475569;
}

.status-badge {
  font-size: 11.5px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 20px;
  display: inline-block;
}

.status-running {
  color: #2563eb;
  background: #dcfce7;
}

.status-expired {
  color: #dc2626;
  background: #fee2e2;
}

.status-open {
  color: #2563eb;
  background: #dbeafe;
}

.actions {
  display: flex;
  gap: 6px;
}

.action-btn {
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
}

.action-btn:hover {
  background: #f1f5f9;
}

.action-btn svg {
  width: 13px;
  height: 13px;
  stroke: #64748b;
  stroke-width: 1.8;
  fill: none;
}

.action-delete:hover {
  background: #fef2f2;
  border-color: #fca5a5;
}

.action-delete:hover svg {
  stroke: #ef4444;
}

.empty-row {
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
  padding: 28px;
}

.loading-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid #e2e8f0;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  margin-right: 8px;
  vertical-align: middle;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.pagination-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 13px 18px;
  border-top: 1px solid #f1f5f9;
}

.page-info {
  font-size: 12.5px;
  color: #64748b;
}

.pagination {
  display: flex;
  gap: 4px;
}

.page-btn {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-size: 12.5px;
  color: #475569;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-btn.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
  font-weight: 600;
}

/* BOTTOM */
.bottom-row { display: grid; grid-template-columns: 1fr; gap: 14px; }
.bottom-card { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid #e8edf5; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.bottom-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.bottom-card-header h3 { font-size: 14px; font-weight: 700; color: #1e293b; }
.icon-btn-sm { width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.icon-btn-sm svg { width: 14px; height: 14px; stroke: #64748b; stroke-width: 2; fill: none; }
.rank-list { display: flex; flex-direction: column; gap: 12px; }
.rank-item { display: flex; align-items: center; gap: 10px; }
.rank-num { font-size: 12px; font-weight: 800; color: #2563eb; width: 24px; flex-shrink: 0; }
.rank-bar-wrap { flex: 1; }
.rank-name { font-size: 12.5px; font-weight: 600; color: #1e293b; margin-bottom: 4px; }
.rank-bar { height: 4px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
.rank-fill { height: 100%; border-radius: 99px; transition: width 0.6s ease; }
.rank-roi { font-size: 12px; font-weight: 700; white-space: nowrap; }

/* ═══ HOLIDAY LIST ═══ */
.holiday-list-wrapper {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  border: 1px solid #e8edf5;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.holiday-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.holiday-item {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 16px;
  border-radius: 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  transition: all 0.2s;
}

.holiday-item:hover {
  background: #fff;
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.next-holiday {
  background: linear-gradient(to right, #eff6ff, #fff);
  border-color: #bfdbfe;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
}

@keyframes holiday-pulse {
  0%   { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.5); }
  50%  { box-shadow: 0 0 0 10px rgba(37, 99, 235, 0); }
  100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
}

.holiday-highlight-pulse {
  animation: holiday-pulse 0.6s ease 3;
  border-color: #2563eb !important;
  background: linear-gradient(to right, #dbeafe, #eff6ff) !important;
}

.holiday-date-box {
  width: 64px;
  height: 64px;
  background: #fff;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  flex-shrink: 0;
}

.next-holiday .holiday-date-box {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
}

.holiday-day {
  font-size: 24px;
  font-weight: 800;
  line-height: 1;
  color: #1e293b;
}

.next-holiday .holiday-day {
  color: #fff;
}

.holiday-month {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  margin-top: 2px;
  text-transform: uppercase;
}

.next-holiday .holiday-month {
  color: #93c5fd;
}

.multi-day {
  background: #f1f5f9;
  border: 1px dashed #cbd5e1;
}

.holiday-day-range {
  font-size: 12px;
  font-weight: 700;
  color: #334155;
}

.holiday-range-sep {
  font-size: 10px;
  color: #94a3b8;
  margin: 2px 0;
}

.holiday-info {
  flex: 1;
}

.holiday-name {
  font-size: 15px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 4px;
}

.next-holiday .holiday-name {
  color: #2563eb;
  font-size: 16px;
}

.holiday-full-date {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.holiday-countdown {
  padding: 6px 12px;
  background: #e2e8f0;
  color: #475569;
  font-weight: 700;
  font-size: 12.5px;
  border-radius: 8px;
}

.next-holiday .holiday-countdown {
  background: #fef08a;
  color: #854d0e;
}

.holiday-actions {
  margin-left: 10px;
}

.auto-send-toggle { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-width: 126px; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 20px; background: #f8fafc; color: #64748b; font-size: 12px; font-weight: 700; cursor: pointer; }
.auto-send-toggle.enabled { background: #ecfdf5; border-color: #86efac; color: #15803d; }
.auto-send-toggle:disabled { opacity: .65; cursor: wait; }
.toggle-track { width: 30px; height: 17px; padding: 2px; border-radius: 999px; background: #94a3b8; display: inline-flex; align-items: center; }
.toggle-knob { width: 13px; height: 13px; border-radius: 50%; background: #fff; transition: transform .2s ease; box-shadow: 0 1px 2px rgba(0,0,0,.2); }
.auto-send-toggle.enabled .toggle-track { background: #22c55e; }
.auto-send-toggle.enabled .toggle-knob { transform: translateX(13px); }

/* ═══ MODAL ═══ */
.overlay {
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
  max-width: 560px;
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

.icon-create {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
}

.icon-edit {
  background: linear-gradient(135deg, #f59e0b, #f97316);
}

.modal-icon svg {
  width: 19px;
  height: 19px;
  stroke: #fff;
  stroke-width: 2.2;
  fill: none;
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
}

.modal-close svg {
  width: 14px;
  height: 14px;
  stroke: #64748b;
  stroke-width: 2;
  fill: none;
}

.modal-close:hover {
  background: #fee2e2;
}

.modal-close:hover svg {
  stroke: #ef4444;
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.form-label {
  font-size: 12.5px;
  font-weight: 600;
  color: #374151;
}

.req {
  color: #ef4444;
}

.form-input {
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

.form-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input.err {
  border-color: #ef4444;
}

.form-textarea {
  resize: vertical;
  min-height: 72px;
}

.mono {
  font-family: monospace;
}

select.form-input {
  cursor: pointer;
}

.err-msg {
  font-size: 11.5px;
  color: #ef4444;
}

.form-hint {
  font-size: 11px;
  color: #94a3b8;
}

.input-suffix-wrap {
  position: relative;
}

.input-suffix-wrap .form-input {
  padding-right: 44px;
}

.input-suffix {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 12px;
  font-weight: 600;
  color: #94a3b8;
  pointer-events: none;
}

/* CONDITION ROW */
.condition-row {
  margin-top: 2px;
  background: #f5f3ff;
  border: 1.5px dashed #a5b4fc;
  border-radius: 12px;
  padding: 12px 14px;
  grid-template-columns: 1.3fr 1fr;
}

.condition-row.freeship-row {
  background: #f0fdf4;
  border-color: #86efac;
}

.condition-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
  background: #ede9fe;
  padding: 3px 10px;
  border-radius: 20px;
}

.freeship-badge {
  color: #1E40AF;
  background: #d1fae5;
}

.condition-select option {
  font-size: 12.5px;
}

.condition-input {
  border-color: #c4b5fd !important;
}

.condition-input:focus {
  border-color: #2563eb !important;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
}

.condition-input.freeship-input {
  border-color: #6ee7b7 !important;
}

.condition-input.freeship-input:focus {
  border-color: #1D4ED8 !important;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12) !important;
}

.toggle-group {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.toggle-btn {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 8px 14px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  background: #f8fafc;
  font-size: 12.5px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}

.toggle-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #cbd5e1;
  flex-shrink: 0;
}

.icon-picker {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.icon-option {
  width: 48px;
  height: 40px;
  border-radius: 10px;
  border: 2px solid transparent;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .03em;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.icon-option:hover {
  border-color: rgba(37, 99, 235, .35);
}

.icon-option-active {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
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

.btn-save {
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

.btn-save:hover:not(:disabled) {
  transform: translateY(-1px);
}

.btn-save:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-save svg {
  width: 14px;
  height: 14px;
  stroke: #fff;
  stroke-width: 2;
  fill: none;
}

.spin {
  animation: spin 0.7s linear infinite;
}

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

.birthday-status-info {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border: 1.5px solid #f59e0b;
  border-radius: 12px;
  font-size: 13px;
  color: #92400e;
}

.birthday-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(146, 64, 14, .12);
  font-size: 11px;
  font-weight: 800;
  flex-shrink: 0;
}

.birthday-status-info strong {
  color: #b45309;
}

/* ═══ INLINE FORM ═══ */
.inline-form-header {
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.inline-form-header h1 {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  margin: 8px 0 4px;
}

.inline-form-header p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: none;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 7px 14px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 12px;
}

.back-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #0f172a;
}

.inline-form-body {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 26px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  box-shadow: 0 8px 28px rgba(15, 23, 42, 0.06);
}

.inline-form-body>.form-group,
.inline-form-body>.form-row>.form-group {
  background: transparent;
  border-radius: 0;
  border: 0;
  padding: 0;
  box-shadow: none;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.inline-form-body>.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.inline-form-body .form-label {
  font-size: 11.5px;
  font-weight: 700;
  color: #6b7280;
  letter-spacing: 0.06em;
  text-transform: capitalize;
}

.inline-form-body .form-input {
  padding: 11px 14px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  font-size: 14px;
  color: #0f172a;
  outline: none;
  transition: all 0.2s;
  background: #f9fafb;
  font-family: inherit;
  width: 100%;
  box-sizing: border-box;
}

.inline-form-body .form-input:focus {
  border-color: #2563eb;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.inline-form-body .form-input.err {
  border-color: #f87171;
  background: #fff5f5;
}

.inline-form-body .form-textarea {
  resize: vertical;
  min-height: 90px;
}

.code-input-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.code-input-row .form-input {
  flex: 1;
}

.btn-gen-code {
  padding: 0 16px;
  height: 44px;
  background: #f1f5f9;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-gen-code:hover:not(:disabled) {
  background: #e2e8f0;
  border-color: #94a3b8;
}

.btn-gen-code:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.inline-form-body>.condition-row {
  background: #f5f3ff;
  border: 1.5px dashed #a5b4fc;
  border-radius: 14px;
  padding: 16px 18px;
}

.inline-form-body>.condition-row>.form-group {
  padding: 0;
  border: 0;
  background: transparent;
  box-shadow: none;
}

@media (max-width: 900px) {
  .inline-form-body {
    padding: 18px;
  }

  .inline-form-body>.form-row {
    grid-template-columns: 1fr;
  }
}

.inline-form-body>.condition-row.freeship-row {
  background: #f0fdf4;
  border-color: #86efac;
}

.inline-form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
  margin-top: 8px;
}

/* HOLIDAY MODAL */
.modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.4); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; }
.holiday-modal { background: #fff; width: 440px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; max-height: 80vh; }
.holiday-header { padding: 18px 22px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
.holiday-header h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; }
.close-btn { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; transition: color 0.2s; line-height: 1; padding: 0; display: flex; align-items: center; justify-content: center; }
.close-btn:hover { color: #ef4444; }
.holiday-body { padding: 0; overflow-y: auto; flex: 1; }
.holiday-list { display: flex; flex-direction: column; }
.holiday-item { display: flex; align-items: center; gap: 16px; padding: 16px 22px; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
.holiday-item:hover { background: #f8fafc; }
.holiday-item:last-child { border-bottom: none; }
.next-holiday { background: #f0fdf4; }
.next-holiday:hover { background: #dcfce7; }
.holiday-date-box { background: #e2e8f0; border-radius: 10px; min-width: 60px; height: 60px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #475569; }
.holiday-date-box.multi-day { min-width: 80px; padding: 0 6px; }
.next-holiday .holiday-date-box { background: #22c55e; color: #fff; }
.holiday-day { font-size: 20px; font-weight: 800; line-height: 1; margin-bottom: 2px; }
.holiday-month { font-size: 11px; font-weight: 600; text-transform: uppercase; }
.holiday-day-range { font-size: 12px; font-weight: 800; line-height: 1.2; }
.holiday-range-sep { font-size: 10px; opacity: 0.7; line-height: 1; }
.holiday-info { flex: 1; }
.holiday-name { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; }
.holiday-full-date { font-size: 12px; color: #64748b; margin: 0; }
.holiday-countdown { font-size: 12px; font-weight: 600; color: #3b82f6; background: #eff6ff; padding: 4px 10px; border-radius: 20px; white-space: nowrap; }
.next-holiday .holiday-countdown { background: #16a34a; color: #fff; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .holiday-modal { animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes slideUp { from { transform: translateY(20px) scale(0.95); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
</style>
