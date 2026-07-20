import { Truck, Shield, RefreshCcw, CreditCard } from 'lucide-react'

export default function WhyChooseUs() {
  const values = [
    {
      icon: Truck,
      title: 'Giao hỏa tốc 2h',
      desc: 'Giao hàng siêu tốc trong nội thành. Nhận hàng nhanh chóng, tiện lợi.'
    },
    {
      icon: Shield,
      title: 'Bảo hành 24 tháng',
      desc: 'Cam kết bảo hành chính hãng. Đổi mới trong 30 ngày đầu nếu có lỗi.'
    },
    {
      icon: RefreshCcw,
      title: 'Thu cũ đổi mới',
      desc: 'Hỗ trợ thu cũ trợ giá khi nâng cấp máy mới. Lên đến 2 triệu đồng.'
    },
    {
      icon: CreditCard,
      title: 'Trả góp 0% lãi suất',
      desc: 'Thủ tục nhanh gọn, duyệt hồ sơ chỉ trong 5 phút. Không cần thế chấp.'
    }
  ]

  return (
    <section className="relative py-28 bg-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background effects */}
      <div className="absolute top-1/2 left-10 w-[400px] h-[400px] bg-indigo-500/5 rounded-full blur-[100px] pointer-events-none z-0"></div>
      <div className="absolute bottom-10 right-10 w-[300px] h-[300px] bg-blue-600/5 rounded-full blur-[80px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-7xl mx-auto relative z-10">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-16 reveal-hidden reveal-visible">
          <span className="text-xs font-semibold text-indigo-400 uppercase tracking-widest bg-indigo-950/40 border border-indigo-500/20 px-4 py-2 rounded-full inline-block">
            Dịch vụ tận tâm
          </span>
          <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-6 mb-6 tracking-tight">
            Giá Trị Xứng Tầm <span className="bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">Thương Hiệu</span>
          </h2>
          <p className="text-slate-400 text-lg font-light leading-relaxed">
            Chúng tôi cam kết mang đến trải nghiệm mua sắm hoàn hảo với dịch vụ chăm sóc khách hàng tận tâm.
          </p>
        </div>

        {/* Values Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {values.map((value, i) => {
            const Icon = value.icon
            return (
              <div 
                key={i} 
                className="group bg-[#0a1628]/40 border border-white/5 rounded-2xl p-8 hover:border-indigo-500/30 transition-all duration-500 hover:transform hover:-translate-y-2"
              >
                {/* Icon */}
                <div className="w-14 h-14 bg-indigo-950/50 border border-indigo-500/20 rounded-xl flex items-center justify-center text-indigo-400 group-hover:bg-indigo-500/20 group-hover:scale-110 transition-all duration-300 mb-6">
                  <Icon className="w-7 h-7" />
                </div>

                {/* Content */}
                <h4 className="font-['Space_Grotesk'] font-bold text-white text-xl mb-3 group-hover:text-indigo-400 transition-colors">
                  {value.title}
                </h4>
                <p className="text-slate-400 text-sm font-light leading-relaxed">
                  {value.desc}
                </p>
              </div>
            )
          })}
        </div>

        {/* Trust Indicators */}
        <div className="mt-16 flex flex-wrap items-center justify-center gap-8 pt-12 border-t border-slate-800/50">
          <div className="text-center">
            <div className="text-3xl font-extrabold font-['Space_Grotesk'] text-white mb-2">
              100<span className="text-indigo-400">%</span>
            </div>
            <div className="text-sm text-slate-500">Hàng chính hãng</div>
          </div>
          <div className="text-center">
            <div className="text-3xl font-extrabold font-['Space_Grotesk'] text-white mb-2">
              24<span className="text-indigo-400">/7</span>
            </div>
            <div className="text-sm text-slate-500">Hỗ trợ khách hàng</div>
          </div>
          <div className="text-center">
            <div className="text-3xl font-extrabold font-['Space_Grotesk'] text-white mb-2">
              30<span className="text-indigo-400">+</span>
            </div>
            <div className="text-sm text-slate-500">Chi nhánh toàn quốc</div>
          </div>
          <div className="text-center">
            <div className="text-3xl font-extrabold font-['Space_Grotesk'] text-white mb-2">
              98<span className="text-indigo-400">%</span>
            </div>
            <div className="text-sm text-slate-500">Khách hàng hài lòng</div>
          </div>
        </div>

      </div>
    </section>
  )
}

