import { Star, ShieldCheck, Quote } from 'lucide-react'

export default function Testimonials() {
  const reviews = [
    {
      name: 'TS. Helena Vance',
      role: 'Trưởng nhóm nghiên cứu AI, Neural Labs',
      quote: 'Trước đây chúng tôi phải thuê máy chủ cloud đắt đỏ để chạy mô hình. Aether giúp thử nghiệm mô hình ngay tại chỗ chỉ trong vài phút.',
      metrics: 'Hiệu năng đã kiểm chứng'
    },
    {
      name: 'Marcus Kaelen',
      role: 'Giám đốc sáng tạo, Cyberpunk Cinema',
      quote: 'Quy trình dựng hình rất nhạy với độ trễ. Cấu hình Aether giúp không gian làm việc yên tĩnh, ổn định và phản hồi nhanh hơn.',
      metrics: 'Quy trình linh hoạt'
    },
    {
      name: 'Kenji Tanaka',
      role: 'Trưởng nhóm thiết kế cơ khí, Apex Robotics',
      quote: 'Tốc độ dựng mô hình CAD tăng rõ rệt. Thiết kế module giúp nâng cấp linh kiện nhanh và gọn hơn rất nhiều.',
      metrics: 'Phần cứng ổn định'
    }
  ]

  return (
    <section className="landing-section relative py-28 bg-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background visual accents */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-cyan-500/5 rounded-full blur-[120px] pointer-events-none z-0"></div>

      <div className="landing-container">
        
        {/* Section Header */}
        <div className="landing-header mb-20 reveal-hidden reveal-visible">
          <span className="text-xs font-mono font-bold text-cyan-400 uppercase tracking-widest bg-cyan-950/40 border border-cyan-500/10 px-3 py-1.5 rounded-full">
            Đánh giá đã xác thực
          </span>
          <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-4 mb-6 tracking-tight">
            Được tin dùng bởi <span className="bg-gradient-to-r from-white to-cyan-300 bg-clip-text text-transparent">người dùng chuyên nghiệp</span>
          </h2>
          <p className="text-slate-400 text-lg font-light leading-relaxed">
            Xem lý do các nhà sáng tạo, kỹ sư và nhóm nghiên cứu chọn Aether cho công việc hiệu năng cao.
          </p>
        </div>

        {/* Reviews Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {reviews.map((review, i) => (
            <div 
              key={i} 
              className="bg-[#0a1628]/40 border border-white/5 p-8 rounded-3xl backdrop-blur-md hover:border-cyan-500/20 transition-all duration-300 flex flex-col justify-between relative group"
            >
              {/* Top Quote Icon Accent */}
              <Quote className="absolute top-6 right-6 w-12 h-12 text-slate-800/40 pointer-events-none group-hover:text-cyan-500/10 transition-colors" />

              <div>
                {/* Rating stars */}
                <div className="flex gap-1 text-cyan-400 mb-6">
                  {[...Array(5)].map((_, idx) => (
                    <Star key={idx} className="w-4 h-4 fill-current" />
                  ))}
                </div>

                <p className="text-slate-300 text-sm font-light leading-relaxed mb-8 relative z-10 italic">
                  "{review.quote}"
                </p>
              </div>

              <div>
                <div className="w-full h-[1px] bg-slate-800/80 mb-6"></div>
                
                <h4 className="font-['Space_Grotesk'] font-bold text-white text-base">
                  {review.name}
                </h4>
                
                <div className="text-xs text-slate-500 mt-0.5">{review.role}</div>
                
                <div className="flex items-center gap-2 mt-4 text-[9px] font-mono text-cyan-400 uppercase tracking-wider bg-cyan-950/40 border border-cyan-500/10 w-fit px-2 py-1 rounded">
                  <ShieldCheck className="w-3 h-3 text-cyan-400" />
                  {review.metrics}
                </div>
              </div>
            </div>
          ))}
        </div>

      </div>
    </section>
  )
}
