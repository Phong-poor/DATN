import { useMagneticButton } from '../hooks/useAnimations'
import { ArrowDown, ChevronRight } from 'lucide-react'

export default function HeroSection() {
  const ctaRef1 = useMagneticButton()
  const ctaRef2 = useMagneticButton()

  return (
    <section className="relative min-h-screen pt-32 pb-20 flex flex-col items-center justify-center overflow-hidden">
      {/* Background gradient effects */}
      <div className="absolute inset-0 bg-gradient-to-b from-[#0a1628] via-[#020817] to-[#020817] z-0"></div>
      <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-600/10 rounded-full blur-[120px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-7xl mx-auto relative z-10">
        
        {/* Main Hero Content */}
        <div className="text-center max-w-5xl mx-auto reveal-hidden reveal-visible">
          
          {/* Badge */}
          <div className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-500/10 border border-indigo-400/20 rounded-full mb-8">
            <span className="w-2 h-2 bg-indigo-400 rounded-full animate-pulse"></span>
            <span className="text-sm font-semibold text-indigo-400 uppercase tracking-wide">
              Chính hãng • Bảo hành 24 tháng
            </span>
          </div>

          {/* Main Headline */}
          <h1 className="hero-title font-['Space_Grotesk'] text-5xl md:text-7xl lg:text-8xl font-extrabold tracking-tight leading-[1.05] mb-8">
            <span className="text-white block">Trải Nghiệm</span>
            <span className="block bg-gradient-to-r from-indigo-400 via-blue-400 to-cyan-400 bg-clip-text text-transparent pb-2">
              Đẳng Cấp
            </span>
            <span className="text-white block">Không Giới Hạn</span>
          </h1>

          {/* Description */}
          <p className="text-slate-300 text-xl md:text-2xl font-light leading-relaxed max-w-3xl mx-auto mb-12">
            Khám phá bộ sưu tập laptop cao cấp được tuyển chọn kỹ lưỡng. 
            Từ gaming powerhouse đến workstation chuyên nghiệp, mọi nhu cầu đều được đáp ứng hoàn hảo.
          </p>

          {/* Action Buttons */}
          <div className="flex flex-wrap items-center justify-center gap-4 mb-16">
            <a 
              ref={ctaRef1}
              href="#products" 
              className="magnetic-btn flex items-center gap-2 px-10 py-5 bg-gradient-to-r from-indigo-600 to-blue-600 rounded-2xl font-bold text-white text-lg shadow-[0_0_40px_rgba(99,102,241,0.3)] hover:shadow-[0_0_50px_rgba(99,102,241,0.5)] transition-all duration-300 border border-indigo-400/20 group"
            >
              Khám phá ngay
              <ChevronRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
            </a>
            
            <a 
              ref={ctaRef2}
              href="#categories" 
              className="magnetic-btn flex items-center gap-2 px-10 py-5 bg-slate-900/60 border border-slate-700/60 hover:border-slate-500/60 rounded-2xl font-bold text-slate-300 hover:text-white text-lg transition-all duration-300 backdrop-blur-sm"
            >
              Xem danh mục
            </a>
          </div>

          {/* Stats Grid */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto bg-slate-900/40 border border-slate-800/80 rounded-2xl p-8 backdrop-blur-sm">
            <div className="text-center">
              <div className="text-3xl md:text-4xl font-extrabold font-['Space_Grotesk'] text-white mb-2">
                134<span className="text-indigo-400">+</span>
              </div>
              <div className="text-sm text-slate-400 font-medium">Dòng sản phẩm</div>
            </div>
            <div className="text-center">
              <div className="text-3xl md:text-4xl font-extrabold font-['Space_Grotesk'] text-white mb-2">
                506<span className="text-indigo-400">k</span>
              </div>
              <div className="text-sm text-slate-400 font-medium">Khách hàng</div>
            </div>
            <div className="text-center">
              <div className="text-3xl md:text-4xl font-extrabold font-['Space_Grotesk'] text-white mb-2">
                38<span className="text-indigo-400">+</span>
              </div>
              <div className="text-sm text-slate-400 font-medium">Thương hiệu</div>
            </div>
            <div className="text-center">
              <div className="text-3xl md:text-4xl font-extrabold font-['Space_Grotesk'] text-white mb-2">
                78.5<span className="text-indigo-400">k</span>
              </div>
              <div className="text-sm text-slate-400 font-medium">Đánh giá 5⭐</div>
            </div>
          </div>
        </div>
      </div>

      {/* Scroll Indicator */}
      <a 
        href="#categories" 
        className="absolute bottom-12 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 group text-slate-400 hover:text-indigo-400 transition-colors z-20 hidden md:flex"
      >
        <span className="text-xs font-medium tracking-wide uppercase">Cuộn xuống</span>
        <div className="p-2 border border-slate-700/80 rounded-full group-hover:border-indigo-400/50 transition-colors bg-slate-900/40 backdrop-blur-sm">
          <ArrowDown className="w-4 h-4 animate-bounce" />
        </div>
      </a>
    </section>
  )
}
