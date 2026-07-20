import { Star, Quote } from 'lucide-react'

export default function Testimonials() {
  const reviews = [
    {
      name: 'Anh Tuấn Nguyễn',
      role: 'Lập trình viên Senior',
      avatar: 'T',
      quote: 'Mua laptop workstation ở đây để chạy Docker và các dự án lớn. Máy chạy cực êm, tản nhiệt rất mát mẻ. Dịch vụ hỗ trợ 24/7 thật sự chuyên nghiệp và nhiệt tình.',
      rating: 5
    },
    {
      name: 'Chị Vy Trần',
      role: 'UI/UX Designer',
      avatar: 'V',
      quote: 'Màn hình OLED hiển thị màu sắc chuẩn chỉnh 100% DCI-P3, hoàn hảo cho công việc thiết kế. Ship hỏa tốc 2 tiếng là nhận được hàng ngay. Rất hài lòng!',
      rating: 5
    },
    {
      name: 'Anh Minh Lê',
      role: 'Content Creator',
      avatar: 'M',
      quote: 'MacBook M4 Pro mua ở đây render video 4K nhanh gấp 3 lần máy cũ. Pin dùng cả ngày không cần sạc. Giá cả hợp lý, bảo hành chu đáo.',
      rating: 5
    }
  ]

  return (
    <section className="relative py-28 bg-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background effects */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-indigo-500/5 rounded-full blur-[120px] pointer-events-none z-0"></div>

      <div className="landing-container">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-20 reveal-hidden reveal-visible">
          <span className="text-xs font-semibold text-indigo-400 uppercase tracking-widest bg-indigo-950/40 border border-indigo-500/20 px-4 py-2 rounded-full inline-block">
            Khách hàng nói gì
          </span>
          <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-6 mb-6 tracking-tight">
            Đồng Hành Cùng <span className="bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">Mọi Luồng Công Việc</span>
          </h2>
          <p className="text-slate-400 text-lg font-light leading-relaxed">
            Hàng nghìn khách hàng đã tin tưởng và hài lòng với sản phẩm, dịch vụ của chúng tôi.
          </p>
        </div>

        {/* Reviews Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {reviews.map((review, i) => (
            <div 
              key={i} 
              className="bg-[#0a1628]/40 border border-white/5 p-8 rounded-3xl backdrop-blur-md hover:border-indigo-500/30 transition-all duration-500 flex flex-col justify-between relative group hover:-translate-y-2"
            >
              {/* Quote Icon */}
              <Quote className="absolute top-6 right-6 w-12 h-12 text-slate-800/40 pointer-events-none group-hover:text-indigo-500/10 transition-colors" />

              <div>
                {/* Rating stars */}
                <div className="flex gap-1 text-yellow-400 mb-6">
                  {[...Array(review.rating)].map((_, idx) => (
                    <Star key={idx} className="w-4 h-4 fill-current" />
                  ))}
                </div>

                <p className="text-slate-300 text-sm font-light leading-relaxed mb-8 relative z-10 italic">
                  "{review.quote}"
                </p>
              </div>

              <div>
                <div className="w-full h-[1px] bg-slate-800/80 mb-6"></div>
                
                {/* User info */}
                <div className="flex items-center gap-3">
                  {/* Avatar */}
                  <div className="w-12 h-12 bg-indigo-950/50 border border-indigo-500/20 rounded-full flex items-center justify-center text-indigo-400 font-bold text-lg">
                    {review.avatar}
                  </div>
                  
                  <div>
                    <h4 className="font-['Space_Grotesk'] font-bold text-white text-base">
                      {review.name}
                    </h4>
                    <div className="text-xs text-slate-500 mt-0.5">{review.role}</div>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>

      </div>
    </section>
  )
}

