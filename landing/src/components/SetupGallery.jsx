export default function SetupGallery() {
  const setups = [
    {
      id: 1,
      title: 'Gaming Console Room',
      desc: 'RGB lighting & góc gaming đỉnh cao',
      image: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&q=80',
      tags: ['Gaming', 'RGB']
    },
    {
      id: 2,
      title: 'Creative Studio Pod',
      desc: 'Tối ưu cho nhạc sĩ & video editor',
      image: 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=600&q=80',
      tags: ['Creative', 'Studio']
    },
    {
      id: 3,
      title: 'Minimalist Workdesk',
      desc: 'Phong cách tối giản tinh tế',
      image: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&q=80',
      tags: ['Minimal', 'Clean']
    }
  ]

  return (
    <section id="gallery" className="relative py-28 bg-gradient-to-b from-[#020817] via-[#0a1628] to-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background effects */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-indigo-500/5 rounded-full blur-[120px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-7xl mx-auto relative z-10">
        
        {/* Header */}
        <div className="max-w-3xl mb-16 reveal-hidden reveal-visible">
          <span className="text-xs font-semibold text-indigo-400 uppercase tracking-widest bg-indigo-950/40 border border-indigo-500/20 px-4 py-2 rounded-full inline-block">
            Cảm hứng thiết kế
          </span>
          <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-6 mb-6 tracking-tight">
            Kiến Tạo Góc Setup <br/>
            <span className="bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">Trong Mơ</span>
          </h2>
          <p className="text-slate-400 text-lg font-light leading-relaxed">
            Khám phá những ý tưởng bố trí bàn làm việc hiện đại, tối ưu không gian sáng tạo của bạn.
          </p>
        </div>

        {/* Gallery Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {setups.map((setup) => (
            <div 
              key={setup.id}
              className="gallery-item group relative rounded-3xl border border-white/5 shadow-2xl overflow-hidden min-h-[320px] cursor-pointer"
            >
              {/* Image */}
              <img 
                src={setup.image} 
                alt={setup.title} 
                className="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
              />
              
              {/* Overlay */}
              <div className="gallery-overlay absolute inset-0 z-20 flex flex-col justify-end p-6">
                <div className="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                  <div className="flex gap-2 mb-3">
                    {setup.tags.map((tag, i) => (
                      <span key={i} className="text-[10px] font-semibold text-indigo-400 uppercase tracking-wider bg-indigo-950/60 border border-indigo-500/20 px-2 py-1 rounded backdrop-blur-sm">
                        {tag}
                      </span>
                    ))}
                  </div>
                  
                  <h3 className="font-['Space_Grotesk'] text-xl font-bold text-white mb-2">
                    {setup.title}
                  </h3>
                  <p className="text-slate-300 text-sm font-light leading-relaxed">
                    {setup.desc}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Bottom CTA */}
        <div className="text-center mt-12">
          <p className="text-slate-400 text-sm mb-4">
            Khám phá thêm nhiều ý tưởng setup tuyệt vời
          </p>
          <a 
            href="#products"
            className="inline-flex items-center gap-2 text-indigo-400 hover:text-white font-semibold transition-colors group"
          >
            Xem bộ sưu tập đầy đủ
            <svg className="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </a>
        </div>

      </div>
    </section>
  )
}

