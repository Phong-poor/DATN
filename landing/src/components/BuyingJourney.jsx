import { useState } from 'react'
import { Cpu, HardDrive, Thermometer, ShieldCheck, ChevronRight, Sparkles } from 'lucide-react'
import { useMagneticButton } from '../hooks/useAnimations'

export default function BuyingJourney() {
  const ctaRef = useMagneticButton()

  // Custom configuration states
  const [cores, setCores] = useState('32')
  const [ram, setRam] = useState('64')
  const [cooling, setCooling] = useState('air')

  // Core prices
  const basePrice = 2499
  
  const corePricing = { '32': 0, '64': 800, '128': 1800 }
  const ramPricing = { '64': 0, '128': 400, '256': 950 }
  const coolingPricing = { air: 0, liquid: 300, cryogenic: 750 }

  // Calculation logic
  const totalPrice = basePrice + corePricing[cores] + ramPricing[ram] + coolingPricing[cooling]

  // Calculated specs
  const getCalculatedSpecs = () => {
    let tflops = 110
    let bandwidth = 640
    let temperature = '35°C'

    if (cores === '64') tflops = 220
    else if (cores === '128') tflops = 420

    if (ram === '128') bandwidth = 960
    else if (ram === '256') bandwidth = 1280

    if (cooling === 'liquid') temperature = '18°C'
    else if (cooling === 'cryogenic') temperature = '-180°C (cách nhiệt)'

    return { tflops, bandwidth, temperature }
  }

  const specs = getCalculatedSpecs()

  return (
    <section id="configurator" className="landing-section relative py-28 bg-gradient-to-b from-[#020817] via-[#091629] to-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background visual accents */}
      <div className="absolute top-1/4 left-1/3 w-[500px] h-[500px] bg-cyan-500/5 rounded-full blur-[120px] pointer-events-none z-0"></div>
      <div className="absolute bottom-1/4 right-1/3 w-[400px] h-[400px] bg-blue-600/5 rounded-full blur-[100px] pointer-events-none z-0"></div>

      <div className="landing-container">
        
        {/* Header Title */}
        <div className="landing-header mb-16 reveal-hidden reveal-visible">
          <span className="text-xs font-mono font-bold text-cyan-400 uppercase tracking-widest bg-cyan-950/40 border border-cyan-500/10 px-3 py-1.5 rounded-full">
            Bộ tùy chỉnh cấu hình
          </span>
          <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-4 mb-6 tracking-tight">
            Tùy chỉnh <span className="bg-gradient-to-r from-cyan-400 via-blue-400 to-white bg-clip-text text-transparent">Aether Neural Rig</span> của bạn
          </h2>
          <p className="text-slate-400 text-lg font-light leading-relaxed">
            Chọn số nhân xử lý, dung lượng bộ nhớ và hệ thống tản nhiệt để tạo cấu hình phù hợp với nhu cầu của bạn.
          </p>
        </div>

        {/* Configurator Core Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
          
          {/* Left Column: Interactive Options Configuration (Cores, RAM, Cooling) */}
          <div className="lg:col-span-7 space-y-8">
            
            {/* CPU Option Block */}
            <div className="bg-[#0a1628]/40 border border-white/5 p-6 sm:p-8 rounded-3xl backdrop-blur-md">
              <div className="flex items-center gap-3 mb-6">
                <Cpu className="w-5 h-5 text-cyan-400" />
                <h3 className="font-['Space_Grotesk'] text-lg font-bold text-white uppercase tracking-wide">
                  1. Chọn nhân xử lý
                </h3>
              </div>
              
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <button 
                  onClick={() => setCores('32')}
                  className={`p-4 rounded-2xl border text-left transition-all duration-300 ${
                    cores === '32' 
                      ? 'bg-gradient-to-br from-cyan-500/15 to-blue-500/15 border-cyan-400 text-white shadow-md' 
                      : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'
                  }`}
                >
                  <div className="font-['Space_Grotesk'] font-bold text-base text-white">32 nhân</div>
                  <div className="text-[10px] font-mono text-cyan-500 mt-1 uppercase tracking-wider">Aether tiêu chuẩn</div>
                  <div className="font-semibold text-sm mt-3 text-slate-300">Đã bao gồm</div>
                </button>
                
                <button 
                  onClick={() => setCores('64')}
                  className={`p-4 rounded-2xl border text-left transition-all duration-300 ${
                    cores === '64' 
                      ? 'bg-gradient-to-br from-cyan-500/15 to-blue-500/15 border-cyan-400 text-white shadow-md' 
                      : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'
                  }`}
                >
                  <div className="font-['Space_Grotesk'] font-bold text-base text-white">64 nhân</div>
                  <div className="text-[10px] font-mono text-cyan-500 mt-1 uppercase tracking-wider">Aether nâng cao</div>
                  <div className="font-semibold text-sm mt-3 text-cyan-400">+$800</div>
                </button>

                <button 
                  onClick={() => setCores('128')}
                  className={`p-4 rounded-2xl border text-left transition-all duration-300 ${
                    cores === '128' 
                      ? 'bg-gradient-to-br from-cyan-500/15 to-blue-500/15 border-cyan-400 text-white shadow-md' 
                      : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'
                  }`}
                >
                  <div className="font-['Space_Grotesk'] font-bold text-base text-white">128 nhân</div>
                  <div className="text-[10px] font-mono text-cyan-500 mt-1 uppercase tracking-wider">Aether tối đa</div>
                  <div className="font-semibold text-sm mt-3 text-cyan-400">+$1,800</div>
                </button>
              </div>
            </div>

            {/* RAM Option Block */}
            <div className="bg-[#0a1628]/40 border border-white/5 p-6 sm:p-8 rounded-3xl backdrop-blur-md">
              <div className="flex items-center gap-3 mb-6">
                <HardDrive className="w-5 h-5 text-cyan-400" />
                <h3 className="font-['Space_Grotesk'] text-lg font-bold text-white uppercase tracking-wide">
                  2. Chọn bộ nhớ RAM
                </h3>
              </div>
              
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <button 
                  onClick={() => setRam('64')}
                  className={`p-4 rounded-2xl border text-left transition-all duration-300 ${
                    ram === '64' 
                      ? 'bg-gradient-to-br from-cyan-500/15 to-blue-500/15 border-cyan-400 text-white shadow-md' 
                      : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'
                  }`}
                >
                  <div className="font-['Space_Grotesk'] font-bold text-base text-white">64GB RAM</div>
                  <div className="text-[10px] font-mono text-cyan-500 mt-1 uppercase tracking-wider">Kênh đôi</div>
                  <div className="font-semibold text-sm mt-3 text-slate-300">Đã bao gồm</div>
                </button>
                
                <button 
                  onClick={() => setRam('128')}
                  className={`p-4 rounded-2xl border text-left transition-all duration-300 ${
                    ram === '128' 
                      ? 'bg-gradient-to-br from-cyan-500/15 to-blue-500/15 border-cyan-400 text-white shadow-md' 
                      : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'
                  }`}
                >
                  <div className="font-['Space_Grotesk'] font-bold text-base text-white">128GB RAM</div>
                  <div className="text-[10px] font-mono text-cyan-500 mt-1 uppercase tracking-wider">Bus tốc độ cao</div>
                  <div className="font-semibold text-sm mt-3 text-cyan-400">+$400</div>
                </button>

                <button 
                  onClick={() => setRam('256')}
                  className={`p-4 rounded-2xl border text-left transition-all duration-300 ${
                    ram === '256' 
                      ? 'bg-gradient-to-br from-cyan-500/15 to-blue-500/15 border-cyan-400 text-white shadow-md' 
                      : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'
                  }`}
                >
                  <div className="font-['Space_Grotesk'] font-bold text-base text-white">256GB RAM</div>
                  <div className="text-[10px] font-mono text-cyan-500 mt-1 uppercase tracking-wider">Bus tốc độ cao</div>
                  <div className="font-semibold text-sm mt-3 text-cyan-400">+$950</div>
                </button>
              </div>
            </div>

            {/* Cooling Option Block */}
            <div className="bg-[#0a1628]/40 border border-white/5 p-6 sm:p-8 rounded-3xl backdrop-blur-md">
              <div className="flex items-center gap-3 mb-6">
                <Thermometer className="w-5 h-5 text-cyan-400" />
                <h3 className="font-['Space_Grotesk'] text-lg font-bold text-white uppercase tracking-wide">
                  3. Chọn hệ thống tản nhiệt
                </h3>
              </div>
              
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <button 
                  onClick={() => setCooling('air')}
                  className={`p-4 rounded-2xl border text-left transition-all duration-300 ${
                    cooling === 'air' 
                      ? 'bg-gradient-to-br from-cyan-500/15 to-blue-500/15 border-cyan-400 text-white shadow-md' 
                      : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'
                  }`}
                >
                  <div className="font-['Space_Grotesk'] font-bold text-base text-white">Tản khí chủ động</div>
                  <div className="text-[10px] font-mono text-cyan-500 mt-1 uppercase tracking-wider">Làm mát cơ bản</div>
                  <div className="font-semibold text-sm mt-3 text-slate-300">Đã bao gồm</div>
                </button>
                
                <button 
                  onClick={() => setCooling('liquid')}
                  className={`p-4 rounded-2xl border text-left transition-all duration-300 ${
                    cooling === 'liquid' 
                      ? 'bg-gradient-to-br from-cyan-500/15 to-blue-500/15 border-cyan-400 text-white shadow-md' 
                      : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'
                  }`}
                >
                  <div className="font-['Space_Grotesk'] font-bold text-base text-white">Tản nhiệt lỏng</div>
                  <div className="text-[10px] font-mono text-cyan-500 mt-1 uppercase tracking-wider">Vòng làm mát</div>
                  <div className="font-semibold text-sm mt-3 text-cyan-400">+$300</div>
                </button>

                <button 
                  onClick={() => setCooling('cryogenic')}
                  className={`p-4 rounded-2xl border text-left transition-all duration-300 ${
                    cooling === 'cryogenic' 
                      ? 'bg-gradient-to-br from-cyan-500/15 to-blue-500/15 border-cyan-400 text-white shadow-md' 
                      : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'
                  }`}
                >
                  <div className="font-['Space_Grotesk'] font-bold text-base text-white">Cryo-Bus Loop</div>
                  <div className="text-[10px] font-mono text-cyan-500 mt-1 uppercase tracking-wider">Siêu làm mát</div>
                  <div className="font-semibold text-sm mt-3 text-cyan-400">+$750</div>
                </button>
              </div>
            </div>

          </div>

          {/* Right Column: Dynamic Price Summary, Specs Output, Checkout CTA */}
          <div className="lg:col-span-5 sticky top-28 space-y-6">
            
            <div className="bg-[#0a1628]/80 border border-cyan-500/20 rounded-3xl p-6 sm:p-8 backdrop-blur-md glow-cyan relative overflow-hidden">
              {/* Top border design accent */}
              <div className="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-cyan-400 to-transparent"></div>
              
              <div className="flex items-center gap-2 mb-6">
                <Sparkles className="w-4 h-4 text-cyan-400 animate-pulse" />
                <span className="font-mono text-[10px] text-cyan-400 font-bold uppercase tracking-widest">Tóm tắt cấu hình</span>
              </div>

              {/* Specs parameters lists */}
              <div className="space-y-4 mb-8">
                <div className="flex justify-between items-center text-sm">
                  <span className="text-slate-500">Nhân xử lý</span>
                  <span className="text-white font-semibold">{cores} nhân</span>
                </div>
                <div className="flex justify-between items-center text-sm">
                  <span className="text-slate-500">Bộ nhớ</span>
                  <span className="text-white font-semibold">{ram}GB RAM Flux</span>
                </div>
                <div className="flex justify-between items-center text-sm">
                  <span className="text-slate-500">Tản nhiệt</span>
                  <span className="text-white font-semibold uppercase">{cooling} cycle</span>
                </div>
                
                <div className="w-full h-[1px] bg-slate-800/80 my-4"></div>

                {/* Simulated Computed System performance parameters */}
                <div className="space-y-3">
                  <div className="flex justify-between text-xs">
                    <span className="text-slate-500">Sức mạnh xử lý</span>
                    <span className="text-cyan-400 font-mono font-semibold">{specs.tflops} TFLOPS FP16</span>
                  </div>
                  <div className="flex justify-between text-xs">
                    <span className="text-slate-500">Băng thông truyền dữ liệu</span>
                    <span className="text-cyan-400 font-mono font-semibold">{specs.bandwidth} GB/s</span>
                  </div>
                  <div className="flex justify-between text-xs">
                    <span className="text-slate-500">Nhiệt độ vận hành</span>
                    <span className="text-cyan-400 font-mono font-semibold">{specs.temperature}</span>
                  </div>
                </div>
              </div>

              {/* Pricing breakdown */}
              <div className="mb-8 pt-6 border-t border-slate-800/80 flex items-baseline justify-between">
                <span className="text-slate-400 text-sm">Tổng giá trị</span>
                <span className="text-4xl sm:text-5xl font-['Space_Grotesk'] font-extrabold text-white">
                  ${totalPrice.toLocaleString()}
                </span>
              </div>

              {/* Dynamic CTA Button */}
              <button 
                ref={ctaRef}
                className="magnetic-btn w-full py-4 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-2xl font-bold text-white shadow-[0_0_30px_rgba(6,182,212,0.25)] hover:shadow-[0_0_40px_rgba(6,182,212,0.5)] transition-all duration-300 border border-cyan-400/20 flex items-center justify-center gap-2 group text-base"
              >
                Hoàn tất cấu hình
                <ChevronRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
              </button>

              <div className="flex items-center justify-center gap-2 mt-4 text-[10px] font-mono text-slate-500 uppercase tracking-wider">
                <ShieldCheck className="w-3.5 h-3.5 text-cyan-400" />
                Bao gồm bảo hành 3 năm cho hệ thống
              </div>

            </div>

          </div>

        </div>

      </div>
    </section>
  )
}
