import { Mail, Send } from 'lucide-react'

export default function FinalCTA() {
  return (
    <section className="relative py-28 bg-gradient-to-b from-[#020817] via-[#0a1628] to-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background effects */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-500/10 rounded-full blur-[140px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-4xl mx-auto relative z-10">
        
        {/* Newsletter Card */}
        <div className="bg-gradient-to-br from-indigo-950/40 via-slate-900/60 to-indigo-950/40 border border-indigo-500/20 rounded-3xl p-8 sm:p-16 text-center backdrop-blur-md relative overflow-hidden">
          {/* Top accent line */}
          <div className="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-indigo-400 to-transparent"></div>
          
          <div className="relative z-10">
            {/* Icon */}
            <div className="w-16 h-16 bg-indigo-950/50 border border-indigo-500/20 rounded-2xl flex items-center justify-center text-indigo-400 mx-auto mb-6">
              <Mail className="w-8 h-8" />
            </div>

            {/* Headline */}
            <h2 className="font-['Space_Grotesk'] text-3xl sm:text-5xl font-extrabold text-white mb-6 tracking-tight leading-tight">
              Đăng Ký Nhận Tin <br/>
              <span className="bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">Khuyến Mãi Mới Nhất</span>
            </h2>

            {/* Description */}
            <p className="text-slate-300 text-base sm:text-lg font-light leading-relaxed max-w-xl mx-auto mb-10">
              Nhận thông tin về sản phẩm mới, ưu đãi đặc biệt và các mẹo công nghệ hữu ích được gửi trực tiếp đến hộp thư của bạn.
            </p>

            {/* Email Form */}
            <form className="flex flex-col sm:flex-row gap-3 max-w-md mx-auto mb-8">
              <input 
                type="email"
                placeholder="Nhập email của bạn..."
                className="flex-1 px-6 py-4 bg-slate-900/60 border border-slate-700/60 rounded-xl text-white placeholder:text-slate-500 focus:outline-none focus:border-indigo-500/50 transition-colors"
              />
              <button 
                type="submit"
                className="px-8 py-4 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 rounded-xl font-bold text-white shadow-[0_0_30px_rgba(99,102,241,0.3)] hover:shadow-[0_0_40px_rgba(99,102,241,0.5)] transition-all duration-300 border border-indigo-400/20 flex items-center justify-center gap-2 group"
              >
                Đăng ký
                <Send className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
              </button>
            </form>

            {/* Privacy note */}
            <p className="text-xs text-slate-500">
              Chúng tôi tôn trọng quyền riêng tư của bạn. Không spam, có thể hủy đăng ký bất cứ lúc nào.
            </p>

          </div>

        </div>

      </div>
    </section>
  )
}

