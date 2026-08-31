import fs from 'node:fs/promises';
import { FileBlob, PresentationFile } from '@oai/artifact-tool';

const starter = 'C:/xampp/htdocs/DATN/.codex-slide-work/template-starter.pptx';
const output = 'C:/xampp/htdocs/DATN/NextGen_BaoVe_DoAn_2026.pptx';
const deck = await PresentationFile.importPptx(await FileBlob.load(starter));
const current = await deck.inspect({ kind: 'slide,textbox,shape,image,table,chart', include: 'id,slide,name,text', maxChars: 1000000 });
const rows = (current.ndjson || '').split(/\r?\n/).filter(Boolean).map((line) => JSON.parse(line));

const setText = (slide, name, value) => {
  const row = rows.find((item) => item.slide === slide && item.name === name && item.kind === 'textbox');
  if (!row) throw new Error(`Missing textbox: slide ${slide}, ${name}`);
  const target = deck.resolve(row.id);
  target.text = value;
};

const slideText = {
  1: {
    'TextBox 49': 'ĐỒ ÁN TỐT NGHIỆP',
    'TextBox 50': 'NEXTGEN — HỆ SINH THÁI BÁN LẺ LAPTOP ĐA NỀN TẢNG',
    'TextBox 52': 'Web • Mobile • Realtime • Loyalty • Affiliate',
    'TextBox 55': 'NHÓM', 'TextBox 56': 'Predator Group',
    'TextBox 59': 'GV HƯỚNG DẪN', 'TextBox 60': 'Lê Hồng Sơn',
    'TextBox 62': 'WEBSITE LAPTOP & ACCESSORIES — 2026'
  },
  2: {
    'TextBox 136': 'THÀNH VIÊN NHÓM',
    'TextBox 140': 'Một sản phẩm hoàn chỉnh được tạo nên từ sự phối hợp của 7 thành viên'
  },
  3: {
    'TextBox 9': '01', 'TextBox 11': 'BÀI TOÁN & GIẢI PHÁP',
    'TextBox 12': 'Không chỉ là một website bán hàng',
    'TextBox 13': 'NextGen kết nối toàn bộ hành trình khách hàng và vận hành doanh nghiệp',
    'TextBox 16': 'GIẢI PHÁP',
    'TextBox 17': 'Một nền tảng đa kênh kết nối mua sắm, thanh toán, chăm sóc và vận hành.',
    'TextBox 20': '01', 'TextBox 21': 'Đa nền tảng',
    'TextBox 24': '02', 'TextBox 25': 'Dữ liệu thật',
    'TextBox 28': '03', 'TextBox 29': 'Vận hành thật',
    'TextBox 30': 'WEB • MOBILE • API • REALTIME'
  },
  4: {
    'TextBox 8': '02', 'TextBox 10': 'Ba nhóm người dùng — một luồng dữ liệu thống nhất',
    'TextBox 11': 'Thiết kế theo vai trò giúp mỗi người nhìn đúng chức năng cần thiết',
    'TextBox 15': '01', 'TextBox 16': 'KHÁCH HÀNG',
    'TextBox 17': 'Tìm kiếm, so sánh, mua hàng, thanh toán, theo dõi đơn, đánh giá và nhận ưu đãi trên web hoặc mobile.',
    'TextBox 21': '02', 'TextBox 22': 'NHÂN VIÊN',
    'TextBox 23': 'Xử lý đơn, tư vấn realtime, chấm công khuôn mặt, theo dõi lịch làm và gửi đơn nghỉ phép.',
    'TextBox 27': '03', 'TextBox 28': 'QUẢN TRỊ VIÊN',
    'TextBox 29': 'Điều hành sản phẩm, tồn kho, marketing, affiliate, tài chính và nhân sự theo hệ thống phân quyền chi tiết.',
    'TextBox 31': 'NEXTGEN — ROLE-BASED EXPERIENCE'
  },
  5: {
    'TextBox 2': '03', 'TextBox 3': 'SẢN PHẨM THỰC TẾ',
    'TextBox 4': 'GIAO DIỆN NEXTGEN',
    'TextBox 5': 'Thiết kế premium, responsive và đồng nhất xuyên suốt hành trình mua sắm',
    'TextBox 14': '01', 'TextBox 15': 'TRANG CHỦ', 'TextBox 25': 'TRẢI NGHIỆM PREMIUM',
    'TextBox 53': '02', 'TextBox 54': 'DANH SÁCH SẢN PHẨM',
    'TextBox 105': '03', 'TextBox 106': 'KHUYẾN MÃI & LOYALTY',
    'TextBox 144': '04', 'TextBox 145': 'LIÊN HỆ & BẢN ĐỒ', 'TextBox 162': 'TƯ VẤN ĐA KÊNH',
    'TextBox 172': 'ĐIỂM NỔI BẬT\nCỦA GIAO DIỆN',
    'TextBox 177': 'Responsive', 'TextBox 178': 'Tối ưu desktop, tablet\nvà thiết bị di động.',
    'TextBox 183': 'Tải theo nhu cầu', 'TextBox 184': 'Lazy loading cho từng\nmodule chức năng.',
    'TextBox 190': 'Dữ liệu realtime', 'TextBox 191': 'Đơn hàng, chat và trạng thái\nđược cập nhật tức thời.',
    'TextBox 196': 'Trải nghiệm liền mạch', 'TextBox 197': 'Từ khám phá đến hậu mãi\ntrên cùng hệ sinh thái.',
    'TextBox 198': 'NEXTGEN LAPTOP ECOSYSTEM', 'TextBox 200': 'GIAO DIỆN THẬT • DỮ LIỆU THẬT • LUỒNG THẬT'
  },
  6: {
    'TextBox 2': '04', 'TextBox 3': 'CUSTOMER JOURNEY',
    'TextBox 4': 'Một hành trình mua hàng khép kín',
    'TextBox 5': 'Từ nhu cầu ban đầu đến đánh giá sau mua',
    'TextBox 14': 'HÀNH TRÌNH CỐT LÕI',
    'TextBox 15': 'Khám phá sản phẩm → chọn biến thể → giỏ hàng → voucher/combo → thanh toán → theo dõi đơn → đánh giá.',
    'TextBox 18': '01', 'TextBox 19': 'Khám phá thông minh', 'TextBox 20': 'Lọc, tìm kiếm, sản phẩm đã xem và danh sách yêu thích giúp rút ngắn quyết định mua.',
    'TextBox 23': '02', 'TextBox 24': 'Thanh toán linh hoạt', 'TextBox 25': 'Hỗ trợ COD, SePay, MoMo và VNPAY với kiểm tra trạng thái giao dịch.',
    'TextBox 28': '03', 'TextBox 29': 'Hậu mãi đầy đủ', 'TextBox 30': 'Theo dõi đơn, đặt lại, hủy, hoàn tiền, tải bằng chứng và đánh giá sau hoàn tất.',
    'TextBox 33': '04', 'TextBox 34': 'Trải nghiệm realtime', 'TextBox 35': 'Chat tư vấn và trạng thái đơn hàng được đồng bộ tức thời giữa khách hàng và quản trị.',
    'TextBox 37': 'GIÁ TRỊ MANG LẠI', 'TextBox 38': 'Ít thao tác hơn  •  Minh bạch hơn  •  Chăm sóc tốt hơn  •  Dễ vận hành hơn',
    'TextBox 40': 'NEXTGEN CUSTOMER JOURNEY'
  },
  7: {
    'TextBox 2': '05', 'TextBox 3': 'GROWTH ENGINE',
    'TextBox 4': 'Tăng trưởng không dừng ở một đơn hàng',
    'TextBox 5': 'Bốn cơ chế kéo khách hàng quay lại và tạo doanh thu mới',
    'TextBox 12': '01', 'TextBox 13': 'Loyalty & Xu', 'TextBox 14': 'Điểm danh và tích Xu', 'TextBox 15': 'Khách hàng nhận Xu, xem lịch sử và sử dụng phần thưởng trong hệ sinh thái.',
    'TextBox 20': '02', 'TextBox 21': 'Voucher thông minh', 'TextBox 22': 'Theo sự kiện & sinh nhật', 'TextBox 23': 'Tự động cấp và gửi voucher đúng thời điểm, đồng thời ngăn gửi trùng trong cùng năm.',
    'TextBox 28': '03', 'TextBox 29': 'Affiliate', 'TextBox 30': 'Đo lường đến chuyển đổi', 'TextBox 31': 'Theo dõi referral/video, chỉ ghi hoa hồng khi đơn đã thanh toán và hoàn tất.',
    'TextBox 36': '04', 'TextBox 37': 'Gamification', 'TextBox 38': 'Vòng quay may mắn', 'TextBox 39': 'Lượt quay hằng ngày và phần thưởng tạo động lực quay lại thường xuyên.',
    'TextBox 43': 'KẾT QUẢ HƯỚNG TỚI', 'TextBox 44': 'Giữ chân khách hàng  •  Tăng chuyển đổi  •  Mở rộng kênh bán  •  Đo lường minh bạch',
    'TextBox 46': 'NEXTGEN GROWTH ECOSYSTEM'
  },
  8: {
    'TextBox 2': '06', 'TextBox 3': 'SYSTEM ARCHITECTURE',
    'TextBox 4': 'Một backend cho mọi nền tảng',
    'TextBox 5': 'Web, mobile và realtime dùng chung nghiệp vụ',
    'TextBox 71': 'KIẾN TRÚC:',
    'TextBox 72': 'Vue 3 Web  +  React Native Mobile  →  Laravel 12 REST API  →  MySQL\nSanctum • Reverb • Queue • Cache • Vite • Expo',
    'TextBox 74': 'NEXTGEN MULTI-PLATFORM ARCHITECTURE'
  },
  9: {
    'TextBox 2': '07', 'TextBox 3': 'DATA & DOMAIN',
    'TextBox 4': 'Dữ liệu được thiết kế quanh nghiệp vụ thực tế',
    'TextBox 5': '53 model và 74 migration bao phủ bán hàng, marketing, affiliate và nhân sự',
    'TextBox 162': 'BỐN MIỀN DỮ LIỆU', 'TextBox 164': 'Commerce', 'TextBox 166': 'Engagement', 'TextBox 168': 'Operations',
    'TextBox 169': 'NEXTGEN DOMAIN-DRIVEN DATA MODEL'
  },
  10: {
    'TextBox 78': '• • • •\n• • • •\n• • • •',
    'TextBox 12': 'Từ thao tác người dùng đến vận hành doanh nghiệp',
    'TextBox 19': 'QUẢN TRỊ', 'TextBox 21': 'KHÁCH HÀNG', 'TextBox 27': 'ADMIN', 'TextBox 33': 'USER',
    'Oval 34': 'Sản phẩm & biến thể', 'Oval 36': 'Tồn kho & đơn hàng', 'Oval 38': 'Khuyến mãi & voucher', 'Oval 40': 'Affiliate & hoa hồng',
    'Oval 42': 'Tin tức & SEO', 'Oval 44': 'Chat & hỗ trợ', 'Oval 46': 'Nhân sự & chấm công', 'Oval 48': 'Báo cáo & nhật ký', 'Oval 50': 'Phân quyền chi tiết',
    'Oval 52': 'Khám phá sản phẩm', 'Oval 54': 'Yêu thích & đã xem', 'Oval 56': 'Giỏ hàng & combo', 'Oval 58': 'Thanh toán đa kênh',
    'Oval 60': 'Theo dõi đơn hàng', 'Oval 62': 'Xu & vòng quay', 'Oval 64': 'Affiliate center', 'Oval 66': 'Chat realtime', 'Oval 68': 'Đánh giá sau mua',
    'Oval 70': 'Web & Mobile', 'Oval 71': 'Sanctum + Reverb'
  },
  11: {
    'TextBox 1': 'BA ĐIỂM TẠO KHÁC BIỆT',
    'TextBox 21': 'Chấm công bằng\nnhận diện khuôn mặt', 'TextBox 22': 'Đăng ký khuôn mặt, check-in/check-out, ca làm, lịch sử và đơn nghỉ trên cùng hệ thống.', 'TextBox 23': '01',
    'TextBox 45': 'Affiliate có ví và\nquy tắc tài chính', 'TextBox 46': 'Hoa hồng theo trạng thái đơn, số dư khả dụng, giữ tiền, rút tiền và chống gửi trùng.', 'TextBox 47': '02',
    'TextBox 71': 'Offline sync và\nrealtime operations', 'TextBox 72': 'Hàng đợi thao tác khi mất mạng kết hợp chat và cập nhật trạng thái đơn theo thời gian thực.', 'TextBox 73': '03'
  },
  12: {
    'TextBox 1': '112/112 TEST ĐẠT',
    'TextBox 19': 'KẾT QUẢ', 'TextBox 21': 'PHÂN BỔ THEO NHÓM',
    'TextBox 22': '112 / 112', 'TextBox 23': '112 TEST',
    'TextBox 26': 'Pass', 'TextBox 27': '100%', 'TextBox 29': 'Fail', 'TextBox 30': '0%',
    'TextBox 33': 'Admin & bảo mật', 'TextBox 34': '36', 'TextBox 36': 'Xác thực tài khoản', 'TextBox 37': '27',
    'TextBox 39': 'Affiliate & ví', 'TextBox 40': '13', 'TextBox 42': 'Marketing & nội dung', 'TextBox 43': '20',
    'TextBox 45': 'Mua hàng & đánh giá', 'TextBox 46': '11', 'TextBox 48': 'Core UI & bảo vệ file', 'TextBox 49': '5',
    'TextBox 51': '', 'TextBox 52': '',
    'TextBox 56': 'BẰNG CHỨNG', 'TextBox 57': '112/112 test đạt với 338 assertions. Frontend production build thành công hơn 2.000 module.',
    'TextBox 59': 'ASSERTIONS', 'TextBox 60': '338', 'TextBox 62': 'NGÀY KIỂM TRA', 'TextBox 63': '29 / 08 / 2026',
    'TextBox 64': 'AUTOMATED QUALITY GATE'
  },
  13: {
    'TextBox 10': 'QUY MÔ SẢN PHẨM', 'TextBox 11': 'Không đo bằng số slide — đo bằng phạm vi nghiệp vụ đã triển khai',
    'TextBox 16': 'THÀNH VIÊN', 'TextBox 17': '07', 'TextBox 19': 'TEST PASS', 'TextBox 20': '112',
    'TextBox 22': 'NỀN TẢNG', 'TextBox 23': 'Web\nMobile • API',
    'TextBox 24': 'Hệ thống gồm 50 controller, 53 model, 74 migration, 26 màn hình web, 37 màn hình quản trị và 23 màn hình mobile.',
    'TextBox 25': 'NEXTGEN PROJECT SCALE'
  },
  14: {
    'TextBox 14': 'LIVE DEMO', 'TextBox 15': 'Một hành trình xuyên suốt\nthay vì trình diễn rời rạc',
    'TextBox 16': 'Demo chứng minh dữ liệu được liên kết từ khách hàng đến quản trị và tăng trưởng.',
    'TextBox 27': 'GIAO DIỆN THỰC TẾ',
    'TextBox 30': '1', 'TextBox 31': 'Khám phá sản phẩm', 'TextBox 34': '2', 'TextBox 35': 'Giỏ hàng & voucher',
    'TextBox 38': '3', 'TextBox 39': 'Thanh toán & đơn hàng', 'TextBox 42': '4', 'TextBox 43': 'Admin xử lý đơn',
    'TextBox 46': '5', 'TextBox 47': 'Affiliate & loyalty', 'TextBox 50': '6', 'TextBox 51': 'Chấm công khuôn mặt',
    'TextBox 52': 'Luồng demo:  Mua hàng  →  Thanh toán  →  Realtime  →  Quản trị  →  Loyalty  →  Face ID',
    'TextBox 54': 'NEXTGEN END-TO-END DEMO'
  },
  15: {
    'TextBox 20': 'KẾT LUẬN', 'TextBox 21': 'NEXTGEN ECOSYSTEM',
    'TextBox 22': 'Kết nối khách hàng, vận hành và tăng trưởng\ntrên web, mobile và realtime.',
    'TextBox 30': 'Q&A • THẢO LUẬN', 'TextBox 31': 'Sẵn sàng trình diễn luồng nghiệp vụ thực tế.',
    'TextBox 32': 'Predator Group  •  NextGen Laptop Ecosystem'
  }
};

for (const [slideNumber, fields] of Object.entries(slideText)) {
  for (const [name, value] of Object.entries(fields)) setText(Number(slideNumber), name, value);
}

const replaceImage = async (slide, name, path) => {
  const row = rows.find((item) => item.slide === slide && item.name === name && item.kind === 'image');
  if (!row) throw new Error(`Missing image: slide ${slide}, ${name}`);
  const image = deck.resolve(row.id);
  const bytes = await fs.readFile(path);
  const frame = image.frame; const crop = image.crop; const geometry = image.geometry;
  image.replace({ blob: bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength), contentType: 'image/png', alt: 'Ảnh giao diện NextGen thực tế' });
  image.frame = frame; image.crop = crop; image.geometry = geometry;
};

await replaceImage(5, 'Picture 16', 'C:/xampp/htdocs/DATN/.codex-slide-work/homepage.png');
await replaceImage(5, 'Picture 17', 'C:/xampp/htdocs/DATN/.codex-slide-work/products.png');
await replaceImage(5, 'Picture 18', 'C:/xampp/htdocs/DATN/.codex-slide-work/promotions.png');
await replaceImage(5, 'Picture 19', 'C:/xampp/htdocs/DATN/.codex-slide-work/contact.png');
await replaceImage(14, 'Picture 58', 'C:/xampp/htdocs/DATN/.codex-slide-work/homepage.png');

const chart1Row = rows.find((item) => item.slide === 12 && item.name === 'Chart 24');
if (chart1Row) {
  const chart = deck.resolve(chart1Row.id);
  chart.title = '';
  chart.titlePlacement = 'none';
  chart.dataLabels = { showValue: false, showPercent: false, showSeriesName: false, showCategoryName: false };
  if (chart.series?.getItemAt) chart.series.getItemAt(0).values = [112];
}

const chart2Row = rows.find((item) => item.slide === 12 && item.name === 'Chart 31');
if (chart2Row) {
  const chart = deck.resolve(chart2Row.id);
  chart.title = '';
  chart.titlePlacement = 'none';
  if (chart.series?.getItemAt) {
    const series = chart.series.getItemAt(0);
    series.categories = ['Admin & bảo mật', 'Xác thực tài khoản', 'Affiliate & ví', 'Marketing & nội dung', 'Mua hàng & đánh giá', 'Core UI & bảo vệ file'];
    series.values = [36, 27, 13, 20, 11, 5];
  }
  chart.dataLabels = {
    showValue: true,
    showPercent: false,
    showSeriesName: false,
    showCategoryName: false,
    position: 'center',
    textStyle: { fontSize: 10, bold: true, fill: '#334155' }
  };
}

// The template originally had seven legend rows. We now use six grouped rows,
// so hide the unused seventh marker to keep the legend visually balanced.
const unusedLegendMarker = rows.find((item) => item.slide === 12 && item.name === 'Oval 50');
if (unusedLegendMarker) {
  const marker = deck.resolve(unusedLegendMarker.id);
  const previousMarkerRow = rows.find((item) => item.slide === 12 && item.name === 'Oval 47');
  // Overlay it exactly on the last used marker. This also hides the template's
  // baked-in shadow, which remains visible when only the fill is made transparent.
  if (previousMarkerRow) {
    const previousMarker = deck.resolve(previousMarkerRow.id);
    marker.frame = previousMarker.frame;
    marker.fill = previousMarker.fill;
    marker.line = previousMarker.line;
  }
}

const tableRow = rows.find((item) => item.slide === 13 && item.name === 'Table 14');
if (tableRow) {
  const table = deck.resolve(tableRow.id);
  const values = [
    ['STT', 'Hạng mục', 'Số lượng', 'Ý nghĩa', 'Web', 'Mobile', 'Backend'],
    ['1', 'Controller', '50', 'Xử lý nghiệp vụ', '✓', '', '✓'],
    ['2', 'Model', '53', 'Miền dữ liệu', '', '', '✓'],
    ['3', 'Migration', '74', 'Tiến hóa CSDL', '', '', '✓'],
    ['4', 'Màn hình khách', '26', 'Trải nghiệm web', '✓', '', ''],
    ['5', 'Màn hình admin', '37', 'Vận hành', '✓', '', ''],
    ['6', 'Màn hình mobile', '23', 'Trải nghiệm di động', '', '✓', ''],
    ['7', 'Automated test', '112', 'Độ tin cậy', '', '', '✓'],
    ['8', 'Assertions', '338', 'Bằng chứng kiểm thử', '', '', '✓']
  ];
  for (let r = 0; r < values.length; r++) {
    for (let c = 0; c < values[r].length; c++) {
      table.cells.set(r, c, values[r][c]);
      const cell = table.getCell(r, c);
      if (cell?.text) cell.text.style = { fontSize: r === 0 ? 12 : 11, bold: r === 0 };
    }
  }
}

const notes = [
  'Mở đầu bằng luận điểm: đây là hệ sinh thái bán lẻ đa nền tảng, không phải website CRUD.',
  'Giới thiệu ngắn vai trò nhóm; không đọc lần lượt quá lâu.',
  'Nêu bài toán và định vị giải pháp theo hành trình vận hành thật.',
  'Nhấn mạnh ba nhóm dùng chung một nguồn dữ liệu và phân quyền.',
  'Cho giảng viên thấy giao diện thật và tính nhất quán.',
  'Kể customer journey từ khám phá đến đánh giá sau mua.',
  'Giải thích cơ chế giữ chân: Xu, voucher, affiliate, gamification.',
  'Trình bày kiến trúc một backend phục vụ web, mobile và realtime.',
  'Nêu bốn miền dữ liệu thay vì đi sâu từng bảng.',
  'Giải thích dữ liệu đi từ khách hàng sang quản trị và dịch vụ nền.',
  'Dừng lại ở ba khác biệt mạnh nhất của đồ án.',
  'Đây là slide bằng chứng: 112 test, 338 assertions, 100% đạt.',
  'Dùng số liệu quy mô để chứng minh phạm vi triển khai.',
  'Chuyển ngay sang demo theo đúng thứ tự trên slide.',
  'Kết luận bằng giá trị hệ thống, sau đó mời câu hỏi.'
];

for (let i = 0; i < deck.slides.items.length; i++) {
  deck.slides.items[i].speakerNotes.textFrame.setText(`${notes[i]}\n\n[Sources]\n- C:/xampp/htdocs/DATN — mã nguồn, số liệu cấu trúc và kết quả test/build nội bộ, kiểm tra ngày 29/08/2026.\n- Ảnh giao diện NextGen chụp trực tiếp trên môi trường local ngày 29/08/2026.`);
}

const pptx = await PresentationFile.exportPptx(deck);
await pptx.save(output);
console.log(output);
