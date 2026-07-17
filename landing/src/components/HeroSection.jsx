import { useTilt, useMagneticButton } from '../hooks/useAnimations'
import { Zap, ArrowDown, ChevronRight } from 'lucide-react'
import quantumCoreImg from '../assets/quantum_core.png'

export default function HeroSection() {
  const tiltRef = useTilt(10)
  const ctaRef1 = useMagneticButton()
  const ctaRef2 = useMagneticButton()

  return (
    <section className="landing-section relative min-h-screen pt-32 pb-20 flex flex-col items-center justify-center overflow-hidden grid-bg">
      {/* Glow Effects in Background */}
      <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-[120px] pointer-events-none z-0"></div>
      <div className="absolute top-1/3 left-1/3 w-[300px] h-[300px] bg-blue-600/10 rounded-full blur-[100px] pointer-events-none z-0"></div>

      {/* Decorative Technical Grid Particle Overlay */}
      <div className="absolute inset-0 pointer-events-none bg-radial-[circle_at_center,_transparent_40%,_#020817_85%] z-0"></div>

      <div className="landing-container grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
        
        {/* Left Text Block */}
        <div className="lg:col-span-7 flex flex-col justify-center text-left reveal-hidden reveal-visible">
          {/* Cyber Badge */}
          <div className="inline-flex items-center gap-2 px-3 py-1.5 bg-cyan-500/10 border border-cyan-400/20 rounded-full w-fit mb-6 animate-pulse">
            <Zap className="w-3.5 h-3.5 text-cyan-400" />
            <span className="text-xs font-mono font-semibold text-cyan-400 uppercase tracking-widest">
              Nhân hiệu năng Quantum Synapse v4.2 đã sẵn sàng
            </span>
          </div>

          {/* Headline */}
          <h1 className="hero-title font-['Space_Grotesk'] text-5xl md:text-7xl font-extrabold tracking-tight leading-[1.05] mb-6">
            <span className="text-white block">Bứt phá sức mạnh</span>
            <span className="gradient-text animate-gradient bg-gradient-to-r from-cyan-400 via-blue-400 to-cyan-400 block pb-2">
              máy tính thế hệ mới
            </span>
          </h1>

          {/* Description */}
          <p className="text-slate-300 text-lg md:text-xl font-light leading-relaxed max-w-xl mb-8">
            Trải nghiệm bộ máy AI hiệu năng cao dành cho sáng tạo, đồ họa và xử lý tác vụ nặng. Thiết kế tản nhiệt tối ưu, phần cứng mạnh mẽ và khả năng nâng cấp linh hoạt.
          </p>

          {/* Action CTAs */}
          <div className="flex flex-wrap items-center gap-4">
            <a 
              ref={ctaRef1}
              href="#configurator" 
              className="magnetic-btn flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-xl font-bold text-white shadow-[0_0_30px_rgba(6,182,212,0.3)] hover:shadow-[0_0_40px_rgba(6,182,212,0.5)] transition-all duration-300 border border-cyan-400/20 group"
            >
              Cấu hình máy ngay
              <ChevronRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
            </a>
            
            <a 
              ref={ctaRef2}
              href="#showcase" 
              className="magnetic-btn flex items-center gap-2 px-8 py-4 bg-slate-900/60 border border-slate-700/60 hover:border-slate-500/60 rounded-xl font-bold text-slate-300 hover:text-white transition-all duration-300 backdrop-blur-sm"
            >
              Khám phá thiết kế
            </a>
          </div>

          {/* Trust Metrics Info */}
          <div className="grid grid-cols-3 gap-6 pt-12 mt-12 border-t border-slate-800/80">
            <div>
              <div className="text-2xl md:text-3xl font-extrabold font-['Space_Grotesk'] text-white">420 <span className="text-cyan-400 text-lg font-medium">TFLOPs</span></div>
              <div className="text-xs font-mono text-slate-500 mt-1 uppercase tracking-wider">Xử lý AI</div>
            </div>
            <div>
              <div className="text-2xl md:text-3xl font-extrabold font-['Space_Grotesk'] text-white">1.2 <span className="text-blue-400 text-lg font-medium">TB/s</span></div>
              <div className="text-xs font-mono text-slate-500 mt-1 uppercase tracking-wider">Băng thông</div>
            </div>
            <div>
              <div className="text-2xl md:text-3xl font-extrabold font-['Space_Grotesk'] text-white">-180°C</div>
              <div className="text-xs font-mono text-slate-500 mt-1 uppercase tracking-wider">Tản nhiệt lỏng</div>
            </div>
          </div>
        </div>

        {/* Right Visual Parallax Block */}
        <div className="lg:col-span-5 flex justify-center items-center relative z-20 mt-10 lg:mt-0">
          
          {/* Rotating Technical Compass Ring */}
          <div className="absolute w-[120%] h-[120%] border border-cyan-500/10 rounded-full animate-rotate-slow pointer-events-none hidden md:block">
            <div className="absolute top-0 left-1/2 w-3 h-3 bg-cyan-400 rounded-full -translate-x-1/2 glow-cyan"></div>
            <div className="absolute bottom-0 left-1/2 w-3 h-3 bg-blue-500 rounded-full -translate-x-1/2 glow-blue"></div>
          </div>

          {/* 3D Tilt Card containing generated quantum core mockup */}
          <div 
            ref={tiltRef}
            className="tilt-card relative w-[320px] sm:w-[380px] h-[380px] sm:h-[440px] rounded-3xl p-1 bg-gradient-to-br from-cyan-400/20 via-blue-500/10 to-slate-900/60 border border-white/10 shadow-[0_0_50px_rgba(2,8,23,0.8)] glow-cyan transition-all duration-300"
          >
            {/* Holographic grid scan line */}
            <div className="absolute inset-0 bg-gradient-to-b from-cyan-400/5 to-transparent h-1/2 pointer-events-none rounded-3xl animate-scan-line"></div>
            
            <div className="w-full h-full rounded-[20px] overflow-hidden bg-slate-950/80 flex flex-col justify-between p-6 relative">
              {/* Top Details */}
              <div className="flex justify-between items-center z-10">
                <span className="font-mono text-[10px] text-cyan-400 tracking-widest uppercase">Trạng thái hệ thống: Đang hoạt động</span>
                <span className="flex h-2 w-2 relative">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                  <span className="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
                </span>
              </div>

              {/* Central Core Rendering */}
              <div className="flex-1 flex items-center justify-center relative select-none">
                {/* Extra floating elements */}
                <div className="absolute w-48 h-48 bg-cyan-500/5 rounded-full blur-xl animate-pulse-glow"></div>
                <img 
                  src={quantumCoreImg} 
                  alt="Nhân xử lý Quantum" 
                  className="w-56 h-56 object-contain animate-float drop-shadow-[0_0_30px_rgba(34,211,238,0.25)] relative z-10"
                />
              </div>

              {/* Bottom Card Interface specs */}
              <div className="z-10 bg-slate-900/80 border border-white/5 rounded-xl p-4 backdrop-blur-md">
                <div className="flex justify-between items-center mb-1.5">
                  <span className="text-white text-xs font-semibold font-['Space_Grotesk'] tracking-wide">AETHER SYNAPSE v4</span>
                  <span className="text-cyan-400 font-mono text-[10px] bg-cyan-950 px-2 py-0.5 rounded">99.8% EFF</span>
                </div>
                <div className="w-full bg-slate-800 rounded-full h-1 overflow-hidden">
                  <div className="bg-gradient-to-r from-cyan-500 to-blue-500 h-full w-[88%] rounded-full"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Downward Scroll Indicator */}
      <a 
        href="#showcase" 
        className="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 group text-slate-400 hover:text-cyan-400 transition-colors z-20 hidden md:flex"
      >
        <span className="text-xs font-mono tracking-widest uppercase">Cuộn xuống</span>
        <div className="p-2 border border-slate-700/80 rounded-full group-hover:border-cyan-400/50 transition-colors bg-slate-900/40 backdrop-blur-sm">
          <ArrowDown className="w-4 h-4 animate-bounce" />
        </div>
      </a>
    </section>
  )
}
