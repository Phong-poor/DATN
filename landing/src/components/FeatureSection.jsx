import { useTilt } from '../hooks/useAnimations'
import { Cpu, Shield, Activity, RefreshCw, Zap, Server } from 'lucide-react'

// Individually wrapped tilt cards so each card has its own tilt ref/state
function FeatureCard({ icon: Icon, title, desc, tag }) {
  const tiltRef = useTilt(12)

  return (
    <div 
      ref={tiltRef}
      className="tilt-card p-[1px] bg-slate-800/40 hover:bg-gradient-to-br hover:from-cyan-400/20 hover:to-blue-500/20 rounded-2xl border border-white/5 transition-all duration-300 hover:shadow-[0_0_30px_rgba(6,182,212,0.15)] group"
    >
      <div className="bg-[#0a1628]/80 hover:bg-slate-950/40 rounded-[15px] p-6 h-full flex flex-col justify-between backdrop-blur-sm">
        <div>
          {/* Icon frame */}
          <div className="w-12 h-12 bg-cyan-950/50 border border-cyan-500/10 rounded-xl flex items-center justify-center text-cyan-400 group-hover:scale-110 group-hover:bg-cyan-500/10 group-hover:text-white transition-all duration-300 mb-6">
            <Icon className="w-6 h-6" />
          </div>
          
          <span className="font-mono text-[10px] text-cyan-500 font-bold uppercase tracking-wider">{tag}</span>
          <h3 className="font-['Space_Grotesk'] text-xl font-bold text-white mt-2 mb-3">
            {title}
          </h3>
          <p className="text-slate-400 text-sm font-light leading-relaxed">
            {desc}
          </p>
        </div>

        {/* Small decorative visual line */}
        <div className="w-6 h-[2px] bg-slate-800 group-hover:w-full group-hover:bg-cyan-500/50 transition-all duration-300 mt-6"></div>
      </div>
    </div>
  )
}

export default function FeatureSection() {
  const features = [
    {
      icon: Cpu,
      tag: 'Ma trận xử lý',
      title: 'Nền tảng silicon tối ưu',
      desc: 'Luồng tín hiệu được tối ưu để giảm nghẽn, giúp hệ thống phản hồi nhanh hơn khi xử lý tác vụ nặng.'
    },
    {
      icon: Server,
      tag: 'Khung máy mát và êm',
      desc: 'Khoang làm mát và luồng khí ổn định giúp máy duy trì hiệu năng khi render, gaming hoặc chạy AI liên tục.',
      title: 'Vòng làm mát Cryogenic'
    },
    {
      icon: Zap,
      tag: 'Kết nối tốc độ cao',
      title: 'Bộ nhớ truyền tải nhanh',
      desc: 'Hệ thống truyền dữ liệu băng thông lớn giúp giảm độ trễ trong quá trình dựng hình và xử lý dữ liệu.'
    },
    {
      icon: Activity,
      tag: 'Tự động tối ưu',
      title: 'Hệ điều phối thông minh',
      desc: 'Các thông số phần cứng được theo dõi và cân bằng để máy hoạt động ổn định trong nhiều tình huống.'
    },
    {
      icon: Shield,
      tag: 'Bảo vệ dữ liệu',
      title: 'Lớp bảo vệ bộ nhớ',
      desc: 'Cơ chế kiểm soát lỗi giúp bảo toàn dữ liệu trong quá trình xử lý tác vụ dài và phức tạp.'
    },
    {
      icon: RefreshCw,
      tag: 'Dễ nâng cấp',
      title: 'Thiết kế module mở rộng',
      desc: 'Cấu trúc linh hoạt giúp nâng cấp linh kiện nhanh, gọn và phù hợp với nhu cầu sử dụng lâu dài.'
    }
  ]

  return (
    <section id="features" className="landing-section relative py-28 bg-gradient-to-b from-[#020817] via-[#071120] to-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background vector accents */}
      <div className="absolute top-10 left-1/2 -translate-x-1/2 w-full max-w-7xl h-full pointer-events-none opacity-20">
        <div className="absolute w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-[120px] top-1/4 -left-20"></div>
        <div className="absolute w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-[120px] bottom-1/4 -right-20"></div>
      </div>

      <div className="landing-container">
        
        {/* Section Title */}
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-16 reveal-hidden reveal-visible">
          <div className="max-w-2xl">
            <span className="text-xs font-mono font-bold text-cyan-400 uppercase tracking-widest bg-cyan-950/40 border border-cyan-500/10 px-3 py-1.5 rounded-full">
              Công nghệ lõi nổi bật
            </span>
            <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-4 mb-4 tracking-tight">
              Tối ưu cho <br/>
              <span className="bg-gradient-to-r from-cyan-400 via-blue-400 to-white bg-clip-text text-transparent">người dùng thế hệ mới</span>
            </h2>
          </div>
          <p className="text-slate-400 font-light text-base max-w-sm mt-4 md:mt-0 md:text-right">
            Mỗi hệ thống được thiết kế để xử lý công việc nặng, giữ hiệu năng ổn định trong thời gian dài.
          </p>
        </div>

        {/* 3D Tilt Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {features.map((feature, index) => (
            <FeatureCard 
              key={index}
              icon={feature.icon}
              tag={feature.tag}
              title={feature.title}
              desc={feature.desc}
            />
          ))}
        </div>

      </div>
    </section>
  )
}
