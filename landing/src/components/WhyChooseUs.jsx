import { ChevronRight, Shield, TrendingUp, Zap } from 'lucide-react'

export default function WhyChooseUs() {
  const benefits = [
    {
      icon: Zap,
      title: 'Hiệu năng mạnh mẽ',
      desc: 'Tối ưu khả năng xử lý để tiết kiệm điện năng nhưng vẫn giữ tốc độ cao trong các tác vụ nặng.'
    },
    {
      icon: TrendingUp,
      title: 'Tăng tốc công việc sáng tạo',
      desc: 'Xử lý AI, render và mô phỏng ngay trên máy cá nhân, giảm phụ thuộc vào dịch vụ đám mây.'
    },
    {
      icon: Shield,
      title: 'Bộ nhớ ổn định',
      desc: 'Cơ chế kiểm soát lỗi giúp dữ liệu an toàn hơn khi chạy tác vụ dài hoặc nhiều ứng dụng cùng lúc.'
    }
  ]

  return (
    <section className="landing-section relative py-28 bg-[#020817] overflow-hidden border-t border-slate-900">
      {/* Visual Accent Lights */}
      <div className="absolute top-1/2 left-10 w-[300px] h-[300px] bg-cyan-500/5 rounded-full blur-[100px] pointer-events-none z-0"></div>
      <div className="absolute bottom-10 right-10 w-[300px] h-[300px] bg-blue-600/5 rounded-full blur-[80px] pointer-events-none z-0"></div>

      <div className="landing-container">
        
        {/* Main Content Layout Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
          
          {/* Left Column: Visual Infographic Chart */}
          <div className="lg:col-span-6 flex flex-col justify-center">
            
            <div className="bg-[#0a1628]/40 border border-white/5 rounded-3xl p-6 sm:p-10 backdrop-blur-md relative overflow-hidden">
              {/* Technical scan matrix lines */}
              <div className="absolute inset-0 bg-gradient-to-b from-cyan-400/5 to-transparent h-1/2 pointer-events-none z-0 animate-scan-line"></div>
              
              <div className="flex justify-between items-center mb-8 relative z-10">
                <span className="font-mono text-xs text-cyan-400 tracking-wider font-semibold uppercase">So sánh hiệu năng</span>
                <span className="text-[10px] font-mono text-slate-500 uppercase">Octane / Neural V4</span>
              </div>

              {/* Diagram comparison bars */}
              <div className="space-y-6 relative z-10 mb-8">
                {/* Aether Rig */}
                <div>
                  <div className="flex justify-between text-sm font-semibold mb-2">
                    <span className="text-white">Aether Neural Rig</span>
                    <span className="text-cyan-400 font-mono">420 TFLOPS</span>
                  </div>
                  <div className="w-full bg-slate-900 rounded-full h-3 overflow-hidden border border-white/5">
                    <div className="bg-gradient-to-r from-cyan-500 to-blue-500 h-full w-full rounded-full glow-cyan"></div>
                  </div>
                </div>

                {/* Top-tier Custom PC */}
                <div>
                  <div className="flex justify-between text-sm font-semibold mb-2">
                    <span className="text-slate-400">Máy trạm cao cấp phổ thông</span>
                    <span className="text-slate-500 font-mono">110 TFLOPS</span>
                  </div>
                  <div className="w-full bg-slate-900 rounded-full h-3 overflow-hidden border border-white/5">
                    <div className="bg-slate-700 h-full w-[26%] rounded-full"></div>
                  </div>
                </div>

                {/* Cloud Node Instance */}
                <div>
                  <div className="flex justify-between text-sm font-semibold mb-2">
                    <span className="text-slate-400">Máy chủ cloud chia sẻ</span>
                    <span className="text-slate-500 font-mono">145 TFLOPS (độ trễ cao)</span>
                  </div>
                  <div className="w-full bg-slate-900 rounded-full h-3 overflow-hidden border border-white/5">
                    <div className="bg-slate-700 h-full w-[34%] rounded-full"></div>
                  </div>
                </div>
              </div>

              {/* Chart Specs Summary */}
              <div className="grid grid-cols-2 gap-4 pt-6 border-t border-slate-800/80 relative z-10">
                <div className="bg-slate-950/60 border border-white/5 p-4 rounded-xl">
                  <div className="text-2xl font-extrabold text-cyan-400 font-['Space_Grotesk']">3.8x</div>
                  <div className="text-[10px] font-mono text-slate-500 mt-1 uppercase tracking-wider">Nhanh hơn máy phổ thông</div>
                </div>
                <div className="bg-slate-950/60 border border-white/5 p-4 rounded-xl">
                  <div className="text-2xl font-extrabold text-white font-['Space_Grotesk']">-84%</div>
                  <div className="text-[10px] font-mono text-slate-500 mt-1 uppercase tracking-wider">Giảm độ trễ xử lý</div>
                </div>
              </div>

            </div>

          </div>

          {/* Right Column: Key Benefits Text */}
          <div className="lg:col-span-6 flex flex-col justify-center">
            
            <span className="text-xs font-mono font-bold text-cyan-400 uppercase tracking-widest bg-cyan-950/40 border border-cyan-500/10 px-3 py-1.5 rounded-full w-fit mb-6">
              Lợi thế kỹ thuật
            </span>
            
            <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mb-6 tracking-tight">
              Giảm độ trễ ngay từ <br/>
              <span className="bg-gradient-to-r from-white to-cyan-300 bg-clip-text text-transparent">nền tảng phần cứng</span>
            </h2>
            
            <p className="text-slate-400 text-base font-light leading-relaxed mb-8">
              Thay vì phụ thuộc hoàn toàn vào cloud, Aether mang lại sức mạnh xử lý trực tiếp tại bàn làm việc của bạn.
            </p>

            {/* List of benefits */}
            <div className="space-y-6">
              {benefits.map((benefit, i) => {
                const Icon = benefit.icon
                return (
                  <div key={i} className="flex gap-4 items-start group">
                    <div className="w-10 h-10 bg-cyan-950/50 border border-cyan-500/10 rounded-xl flex items-center justify-center text-cyan-400 group-hover:bg-cyan-500/10 group-hover:text-white transition-all duration-300 shrink-0">
                      <Icon className="w-5 h-5" />
                    </div>
                    <div>
                      <h4 className="font-['Space_Grotesk'] font-bold text-white text-base mb-1 group-hover:text-cyan-400 transition-colors">
                        {benefit.title}
                      </h4>
                      <p className="text-slate-400 text-sm font-light leading-relaxed">
                        {benefit.desc}
                      </p>
                    </div>
                  </div>
                )
              })}
            </div>

            {/* Call to config redirect */}
            <a 
              href="#configurator" 
              className="flex items-center gap-2 text-cyan-400 font-semibold text-sm hover:text-white transition-colors group mt-10 w-fit"
            >
              Cấu hình bộ máy hiệu năng
              <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
            </a>

          </div>

        </div>

      </div>
    </section>
  )
}
