import { Laptop, Phone, Mail, MapPin } from 'lucide-react'

export default function Footer() {
  return (
    <footer className="relative bg-[#020817] border-t border-slate-900 pt-20 pb-10 overflow-hidden">
      {/* Background glow */}
      <div className="absolute bottom-0 right-0 w-[300px] h-[300px] bg-indigo-500/5 rounded-full blur-[100px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-7xl mx-auto relative z-10">
        
        {/* Main Footer Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
          
          {/* Brand & Contact */}
          <div className="lg:col-span-1 space-y-6">
            <a href="#" className="flex items-center gap-2 group w-fit">
              <div className="p-2 bg-gradient-to-tr from-indigo-600 to-blue-600 rounded-lg text-white shadow-[0_0_15px_rgba(99,102,241,0.3)]">
                <Laptop className="w-5 h-5" />
              </div>
              <span className="font-['Space_Grotesk'] font-bold text-xl tracking-wider text-white">
                Nexzen
              </span>
            </a>
            
            <p className="text-slate-400 text-sm font-light leading-relaxed">
              Cung cấp laptop cao cấp chính hãng với dịch vụ tận tâm. Đồng hành cùng bạn trên mọi hành trình công việc.
            </p>

            {/* Contact Info */}
            <div className="space-y-3 text-sm text-slate-400">
              <div className="flex items-center gap-2">
                <Phone className="w-4 h-4 text-indigo-400" />
                <span>Hotline: 1900 xxxx</span>
              </div>
              <div className="flex items-center gap-2">
                <Mail className="w-4 h-4 text-indigo-400" />
                <span>support@nexzen.vn</span>
              </div>
              <div className="flex items-center gap-2">
                <MapPin className="w-4 h-4 text-indigo-400" />
                <span>Hà Nội, TP. HCM</span>
              </div>
            </div>
          </div>

          {/* Products */}
          <div>
            <h4 className="font-['Space_Grotesk'] font-bold text-white text-sm uppercase tracking-wider mb-6">Sản phẩm</h4>
            <ul className="space-y-3.5 text-sm">
              <li><a href="#categories" className="text-slate-400 hover:text-indigo-400 transition-colors">Laptop Gaming</a></li>
              <li><a href="#categories" className="text-slate-400 hover:text-indigo-400 transition-colors">Laptop Workstation</a></li>
              <li><a href="#categories" className="text-slate-400 hover:text-indigo-400 transition-colors">Laptop Văn phòng</a></li>
              <li><a href="#categories" className="text-slate-400 hover:text-indigo-400 transition-colors">MacBook Series</a></li>
            </ul>
          </div>

          {/* Support */}
          <div>
            <h4 className="font-['Space_Grotesk'] font-bold text-white text-sm uppercase tracking-wider mb-6">Hỗ trợ</h4>
            <ul className="space-y-3.5 text-sm">
              <li><a href="#" className="text-slate-400 hover:text-indigo-400 transition-colors">Chính sách bảo hành</a></li>
              <li><a href="#" className="text-slate-400 hover:text-indigo-400 transition-colors">Hướng dẫn mua hàng</a></li>
              <li><a href="#" className="text-slate-400 hover:text-indigo-400 transition-colors">Chính sách đổi trả</a></li>
              <li><a href="#" className="text-slate-400 hover:text-indigo-400 transition-colors">Câu hỏi thường gặp</a></li>
            </ul>
          </div>

          {/* Company */}
          <div>
            <h4 className="font-['Space_Grotesk'] font-bold text-white text-sm uppercase tracking-wider mb-6">Về chúng tôi</h4>
            <ul className="space-y-3.5 text-sm">
              <li><a href="#" className="text-slate-400 hover:text-indigo-400 transition-colors">Giới thiệu</a></li>
              <li><a href="#" className="text-slate-400 hover:text-indigo-400 transition-colors">Hệ thống cửa hàng</a></li>
              <li><a href="#" className="text-slate-400 hover:text-indigo-400 transition-colors">Tin tức công nghệ</a></li>
              <li><a href="#" className="text-slate-400 hover:text-indigo-400 transition-colors">Tuyển dụng</a></li>
            </ul>
          </div>

        </div>

        {/* Social & Payment */}
        <div className="flex flex-col md:flex-row items-center justify-between gap-6 py-8 border-t border-slate-900">
          {/* Social Links */}
          <div className="flex gap-3">
            <a href="#" className="p-2.5 text-slate-500 hover:text-indigo-400 hover:bg-slate-900 rounded-lg transition-colors border border-transparent hover:border-slate-800">
              <svg className="w-5 h-5 fill-current" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </a>
            <a href="#" className="p-2.5 text-slate-500 hover:text-indigo-400 hover:bg-slate-900 rounded-lg transition-colors border border-transparent hover:border-slate-800">
              <svg className="w-5 h-5 fill-current" viewBox="0 0 24 24">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
              </svg>
            </a>
            <a href="#" className="p-2.5 text-slate-500 hover:text-indigo-400 hover:bg-slate-900 rounded-lg transition-colors border border-transparent hover:border-slate-800">
              <svg className="w-5 h-5 fill-current" viewBox="0 0 24 24">
                <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
              </svg>
            </a>
          </div>

          {/* Payment Methods */}
          <div className="flex items-center gap-3">
            <span className="text-xs text-slate-500 uppercase tracking-wider">Thanh toán:</span>
            <div className="flex gap-2">
              <div className="px-3 py-1.5 bg-slate-900 border border-slate-800 rounded text-xs font-semibold text-slate-400">Visa</div>
              <div className="px-3 py-1.5 bg-slate-900 border border-slate-800 rounded text-xs font-semibold text-slate-400">MasterCard</div>
              <div className="px-3 py-1.5 bg-slate-900 border border-slate-800 rounded text-xs font-semibold text-slate-400">Momo</div>
            </div>
          </div>
        </div>

        {/* Copyright */}
        <div className="pt-8 border-t border-slate-900 text-center text-xs text-slate-500">
          © {new Date().getFullYear()} Nexzen. All Rights Reserved.
        </div>

      </div>
    </footer>
  )
}

