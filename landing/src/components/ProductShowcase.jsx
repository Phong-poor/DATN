export default function ProductShowcase() {
  const categories = [
    {
      id: 1,
      name: 'Gaming Laptop',
      title: 'Gaming Powerhouse',
      desc: 'RTX 4060+ & màn hình 165Hz',
      image: 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?w=600&q=80',
      specs: [
        'RTX 4060/4070/4090',
        'CPU Intel i7/i9 Gen 13+',
        'RAM 16-32GB DDR5',
        'Màn hình 144-240Hz'
      ]
    },
    {
      id: 2,
      name: 'Creative Workstation',
      title: 'Workstation Sáng Tạo',
      desc: 'Màn hình 4K OLED & GPU chuyên nghiệp',
      image: 'https://images.unsplash.com/photo-1593642532400-2682810df593?w=600&q=80',
      specs: [
        'NVIDIA RTX A-series',
        'Intel Xeon / AMD Ryzen',
        'RAM 32-64GB ECC',
        'Màn 4K 100% DCI-P3'
      ]
    },
    {
      id: 3,
      name: 'Business Ultrabook',
      title: 'Ultrabook Cao Cấp',
      desc: 'Siêu mỏng nhẹ & pin cả ngày',
      image: 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=600&q=80',
      specs: [
        'Trọng lượng < 1.3kg',
        'Pin 12-16 giờ',
        'Bảo mật vân tay/Face ID',
        'Thunderbolt 4'
      ]
    },
    {
      id: 4,
      name: 'MacBook Series',
      title: 'MacBook Pro/Air',
      desc: 'Apple Silicon M3/M4 chips',
      image: 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&q=80',
      specs: [
        'Chip M3/M4 Pro/Max',
        'Màn Liquid Retina XDR',
        'RAM 16-96GB unified',
        'Pin 18-22 giờ'
      ]
    }
  ]

  return (
    <section id="categories" className="relative py-28 bg-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background accents */}
      <div className="absolute top-1/2 right-10 w-[400px] h-[400px] bg-indigo-500/5 rounded-full blur-[100px] pointer-events-none z-0"></div>
      <div className="absolute bottom-10 left-10 w-[300px] h-[300px] bg-blue-600/5 rounded-full blur-[80px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-7xl mx-auto relative z-10">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-16 reveal-hidden reveal-visible">
          <span className="text-xs font-semibold text-indigo-400 uppercase tracking-widest bg-indigo-950/40 border border-indigo-500/20 px-4 py-2 rounded-full inline-block">
            Phân khúc laptop chuyên biệt
          </span>
          <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-6 mb-6 tracking-tight">
            Tuyển chọn máy tính <span className="bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">tối ưu hóa cấu hình</span>
          </h2>
          <p className="text-slate-400 text-lg font-light leading-relaxed">
            Mỗi dòng laptop được thiết kế riêng biệt cho từng nhu cầu sử dụng. Từ gaming mạnh mẽ đến workstation chuyên nghiệp.
          </p>
        </div>

        {/* Categories Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          {categories.map((category) => (
            <div 
              key={category.id}
              className="group relative bg-[#0a1628]/40 border border-white/5 rounded-3xl overflow-hidden hover:border-indigo-500/30 transition-all duration-500 cursor-pointer"
            >
              {/* Image Section */}
              <div className="relative h-64 overflow-hidden">
                <img 
                  src={category.image} 
                  alt={category.title}
                  className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-[#0a1628] via-[#0a1628]/60 to-transparent"></div>
                
                {/* Overlay badge */}
                <div className="absolute top-4 left-4 bg-slate-900/80 backdrop-blur-md border border-white/10 px-3 py-1.5 rounded-full">
                  <span className="text-xs font-semibold text-indigo-400 uppercase tracking-wide">{category.name}</span>
                </div>
              </div>

              {/* Content Section */}
              <div className="p-6 sm:p-8">
                <h3 className="font-['Space_Grotesk'] text-2xl font-bold text-white mb-2 group-hover:text-indigo-400 transition-colors">
                  {category.title}
                </h3>
                <p className="text-slate-400 text-base mb-6">
                  {category.desc}
                </p>

                {/* Specs Grid */}
                <div className="grid grid-cols-2 gap-3">
                  {category.specs.map((spec, i) => (
                    <div key={i} className="flex items-center gap-2 text-sm">
                      <div className="w-1.5 h-1.5 bg-indigo-400 rounded-full"></div>
                      <span className="text-slate-300 font-medium">{spec}</span>
                    </div>
                  ))}
                </div>

                {/* CTA */}
                <button className="mt-6 w-full py-3 bg-slate-900/60 hover:bg-indigo-600/20 border border-slate-700/60 hover:border-indigo-500/50 rounded-xl font-semibold text-slate-300 hover:text-white transition-all duration-300 text-sm">
                  Xem chi tiết →
                </button>
              </div>
            </div>
          ))}
        </div>

        {/* Bottom CTA */}
        <div className="text-center mt-16">
          <a 
            href="#products"
            className="inline-flex items-center gap-2 text-indigo-400 hover:text-white font-semibold transition-colors group"
          >
            Xem tất cả danh mục
            <svg className="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </a>
        </div>

      </div>
    </section>
  )
}

