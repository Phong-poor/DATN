<template>
  <div class="labs-page">
    <!-- Cyberpunk Animated Background Elements -->
    <div class="cyber-grid-bg"></div>
    <div class="cyber-scanline"></div>
    <div class="ambient-glow cyan"></div>
    <div class="ambient-glow purple"></div>

    <!-- Hero / Header Section -->
    <div class="labs-hero">
      <div class="hero-bg-overlay"></div>
      <div class="container hero-content">
        <div class="badge-label animate-fade-in">🔬 VinaTech Interactive & Gamification Hub</div>
        <h1 class="animate-slide-up">Đấu Trường Tương Tác & Đại Sứ Công Nghệ</h1>
        <p class="animate-fade-in-delayed">
          Khám phá giới hạn phần cứng, thiết kế laptop cá nhân và tham gia hệ sinh thái nhiệm vụ nhận quà độc quyền.
        </p>

        <!-- Tab Switcher (Expanded to 3 tabs) -->
        <div class="tabs-container">
          <button 
            @click="activeTab = 'versus'" 
            :class="{ active: activeTab === 'versus' }" 
            class="tab-btn"
          >
            <span class="tab-icon">⚔️</span> Đấu Trường Hiệu Năng
          </button>
          <button 
            @click="activeTab = 'customizer'" 
            :class="{ active: activeTab === 'customizer' }" 
            class="tab-btn"
          >
            <span class="tab-icon">🎨</span> Cá Nhân Hóa Laptop
          </button>
          <button 
            @click="activeTab = 'gamification'" 
            :class="{ active: activeTab === 'gamification' }" 
            class="tab-btn"
          >
            <span class="tab-icon">💎</span> Đại Sứ & Quests
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="container main-content">
      <transition name="fade-slide" mode="out-in">
        
        <!-- =================== TAB 1: VERSUS ARENA =================== -->
        <div v-if="activeTab === 'versus'" class="versus-arena" key="versus">
          <div class="grid-layout">
            
            <!-- Column 1: Laptop A Selection -->
            <div class="control-panel card-glass">
              <div class="panel-header">
                <span class="dot-indicator blue"></span>
                <h3>Chiến Binh A (Laptop A)</h3>
              </div>
              
              <div class="form-group">
                <label>Chọn Laptop đối đầu:</label>
                <div class="select-wrapper">
                  <select v-model="selectedIdA" class="custom-select">
                    <option v-for="lap in allLaptops" :key="'A-'+lap.id" :value="lap.id">
                      {{ lap.name }}
                    </option>
                  </select>
                </div>
              </div>

              <div v-if="laptopA" class="selected-product-card animate-fade-in">
                <img :src="laptopA.img" :alt="laptopA.name" class="product-img" />
                <div class="product-info">
                  <h4>{{ laptopA.name }}</h4>
                  <p class="price">{{ formatPrice(laptopA.price) }}</p>
                  <ul class="specs-list">
                    <li><strong class="spec-label">CPU:</strong> {{ laptopA.cpu }}</li>
                    <li><strong class="spec-label">GPU:</strong> {{ laptopA.gpu }}</li>
                    <li><strong class="spec-label">RAM:</strong> {{ laptopA.ram }}</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Column 2: RADAR CHART (Middle Arena) -->
            <div class="chart-panel card-glass center-align">
              <div class="panel-header center-head">
                <h3>ĐỐI ĐẦU CHỈ SỐ SỨC MẠNH</h3>
              </div>

              <!-- Dynamic SVG Radar Chart -->
              <div class="radar-container">
                <svg viewBox="0 0 300 300" class="radar-svg">
                  <!-- Concentric pentagons for background grid -->
                  <polygon v-for="grid in [20, 40, 60, 80, 100]" :key="'grid-'+grid"
                    :points="getGridPoints(grid)" 
                    class="grid-polygon" 
                  />
                  
                  <!-- Circular Grid Radiants (concentric rings) -->
                  <circle cx="150" cy="150" r="100" class="grid-circle" />
                  <circle cx="150" cy="150" r="75" class="grid-circle" />
                  <circle cx="150" cy="150" r="50" class="grid-circle" />
                  <circle cx="150" cy="150" r="25" class="grid-circle" />

                  <!-- Axis Lines -->
                  <line v-for="i in 5" :key="'axis-'+i"
                    x1="150" y1="150" 
                    :x2="getAxisEndCoords(i-1).x" 
                    :y2="getAxisEndCoords(i-1).y" 
                    class="axis-line" 
                  />

                  <!-- Grid Text Indicators -->
                  <text x="150" y="44" class="grid-text">100</text>
                  <text x="150" y="70" class="grid-text">75</text>
                  <text x="150" y="95" class="grid-text">50</text>
                  <text x="150" y="120" class="grid-text">25</text>

                  <!-- Laptop A Polygon (Neon Cyan) -->
                  <polygon 
                    v-if="laptopA" 
                    :points="getPolygonPoints(laptopA)" 
                    class="radar-poly poly-a" 
                  />
                  <!-- Laptop B Polygon (Neon Pink) -->
                  <polygon 
                    v-if="laptopB" 
                    :points="getPolygonPoints(laptopB)" 
                    class="radar-poly poly-b" 
                  />

                  <!-- Label Texts -->
                  <text x="150" y="25" text-anchor="middle" class="axis-label">CPU (Xử lý)</text>
                  <text x="260" y="105" text-anchor="start" class="axis-label">GPU (Game/Đồ họa)</text>
                  <text x="235" y="235" text-anchor="start" class="axis-label">Pin (Dung lượng)</text>
                  <text x="65" y="235" text-anchor="end" class="axis-label">Cơ động (Khối lượng)</text>
                  <text x="40" y="105" text-anchor="end" class="axis-label">Tản nhiệt (Mát)</text>
                </svg>
              </div>

              <!-- Legends -->
              <div class="radar-legends">
                <div class="legend-item" v-if="laptopA">
                  <span class="legend-color color-a"></span>
                  <span class="legend-name">{{ laptopA.name }}</span>
                </div>
                <div class="legend-item" v-if="laptopB">
                  <span class="legend-color color-b"></span>
                  <span class="legend-name">{{ laptopB.name }}</span>
                </div>
              </div>
            </div>

            <!-- Column 3: Laptop B Selection -->
            <div class="control-panel card-glass">
              <div class="panel-header">
                <span class="dot-indicator pink"></span>
                <h3>Chiến Binh B (Laptop B)</h3>
              </div>
              
              <div class="form-group">
                <label>Chọn Laptop đối đầu:</label>
                <div class="select-wrapper">
                  <select v-model="selectedIdB" class="custom-select">
                    <option v-for="lap in allLaptops" :key="'B-'+lap.id" :value="lap.id">
                      {{ lap.name }}
                    </option>
                  </select>
                </div>
              </div>

              <div v-if="laptopB" class="selected-product-card animate-fade-in">
                <img :src="laptopB.img" :alt="laptopB.name" class="product-img" />
                <div class="product-info">
                  <h4>{{ laptopB.name }}</h4>
                  <p class="price">{{ formatPrice(laptopB.price) }}</p>
                  <ul class="specs-list">
                    <li><strong class="spec-label">CPU:</strong> {{ laptopB.cpu }}</li>
                    <li><strong class="spec-label">GPU:</strong> {{ laptopB.gpu }}</li>
                    <li><strong class="spec-label">RAM:</strong> {{ laptopB.ram }}</li>
                  </ul>
                </div>
              </div>
            </div>

          </div>

          <!-- Bottom: FPS & Simulation Arena -->
          <div class="simulation-section card-glass">
            <div class="simulation-header">
              <span class="sim-icon">🎮</span>
              <div>
                <h3>Đấu Trường Giả Lập Hiệu Năng Game Thực Tế</h3>
                <p>Chọn tựa game và độ phân giải đồ họa để mô phỏng chỉ số FPS & Nhiệt độ hoạt động</p>
              </div>
            </div>

            <div class="sim-grid">
              <!-- Game Selectors -->
              <div class="games-selector">
                <label class="section-sublabel">1. Chọn tựa Game muốn thử:</label>
                <div class="games-grid">
                  <button 
                    v-for="game in games" 
                    :key="game.id" 
                    @click="selectedGameId = game.id"
                    :class="{ active: selectedGameId === game.id }"
                    class="game-card"
                  >
                    <span class="game-emoji">{{ game.emoji }}</span>
                    <div class="game-meta">
                      <span class="game-name">{{ game.name }}</span>
                      <span class="game-genre">{{ game.genre }}</span>
                    </div>
                  </button>
                </div>
              </div>

              <!-- Graphics Settings -->
              <div class="graphics-selector">
                <label class="section-sublabel">2. Thiết lập cấu hình đồ họa:</label>
                <div class="slider-wrapper">
                  <div class="setting-labels">
                    <span :class="{ active: graphicsSetting === 'low' }">Thấp (1080p)</span>
                    <span :class="{ active: graphicsSetting === 'medium' }">Trung bình (2K)</span>
                    <span :class="{ active: graphicsSetting === 'high' }">Cực cao (4K Ultra)</span>
                  </div>
                  <input 
                    type="range" 
                    min="1" 
                    max="3" 
                    step="1" 
                    v-model="graphicsSliderVal"
                    class="custom-range"
                  />
                  <div class="setting-description">
                    ⚡ Đang mô phỏng cấu hình: <strong>{{ graphicsSettingLabel }}</strong>
                  </div>
                </div>
              </div>
            </div>

            <!-- Comparison Results Bars -->
            <div class="comparison-results" v-if="laptopA && laptopB">
              <div class="versus-title-middle">RESULTS MATCH</div>
              
              <div class="results-grid">
                <!-- Stat Card: FPS -->
                <div class="stat-compare-card">
                  <div class="stat-compare-title">🎮 Khung hình mỗi giây (FPS)</div>
                  
                  <!-- Laptop A Bar -->
                  <div class="bar-wrapper">
                    <div class="bar-info">
                      <span>{{ laptopA.name }}</span>
                      <span class="value cyan-text">{{ fpsA }} FPS</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill cyan-bg" :style="{ width: getPercentage(fpsA, 360) + '%' }"></div>
                    </div>
                  </div>

                  <!-- Laptop B Bar -->
                  <div class="bar-wrapper">
                    <div class="bar-info">
                      <span>{{ laptopB.name }}</span>
                      <span class="value pink-text">{{ fpsB }} FPS</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill pink-bg" :style="{ width: getPercentage(fpsB, 360) + '%' }"></div>
                    </div>
                  </div>
                  <p class="stat-summary">Mượt mà tối thiểu: 60 FPS. Màn gaming lý tưởng: 144+ FPS.</p>
                </div>

                <!-- Stat Card: TEMPERATURE -->
                <div class="stat-compare-card">
                  <div class="stat-compare-title">🌡️ Nhiệt độ hoạt động (°C)</div>
                  
                  <!-- Laptop A Bar -->
                  <div class="bar-wrapper">
                    <div class="bar-info">
                      <span>{{ laptopA.name }}</span>
                      <span class="value" :class="getTempClass(tempA)">{{ tempA }} °C</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill" :class="getTempBgClass(tempA)" :style="{ width: getPercentage(tempA, 105) + '%' }"></div>
                    </div>
                  </div>

                  <!-- Laptop B Bar -->
                  <div class="bar-wrapper">
                    <div class="bar-info">
                      <span>{{ laptopB.name }}</span>
                      <span class="value" :class="getTempClass(tempB)">{{ tempB }} °C</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill" :class="getTempBgClass(tempB)" :style="{ width: getPercentage(tempB, 105) + '%' }"></div>
                    </div>
                  </div>
                  <p class="stat-summary" v-if="tempA > 85 || tempB > 85">⚠️ Cảnh báo: Nhiệt độ vượt ngưỡng 85°C có thể gây giảm hiệu năng nhẹ (thermal throttling).</p>
                  <p class="stat-summary" v-else>✅ Nhiệt độ hoạt động trong ngưỡng an toàn, hệ thống tản nhiệt hoạt động xuất sắc.</p>
                </div>

                <!-- Stat Card: POWER DRAW -->
                <div class="stat-compare-card">
                  <div class="stat-compare-title">⚡ Điện năng tiêu thụ (TDP Watts)</div>
                  
                  <!-- Laptop A Bar -->
                  <div class="bar-wrapper">
                    <div class="bar-info">
                      <span>{{ laptopA.name }}</span>
                      <span class="value cyan-text">{{ tdpA }}W</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill cyan-bg" :style="{ width: getPercentage(tdpA, 280) + '%' }"></div>
                    </div>
                  </div>

                  <!-- Laptop B Bar -->
                  <div class="bar-wrapper">
                    <div class="bar-info">
                      <span>{{ laptopB.name }}</span>
                      <span class="value pink-text">{{ tdpB }}W</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill pink-bg" :style="{ width: getPercentage(tdpB, 280) + '%' }"></div>
                    </div>
                  </div>
                  <p class="stat-summary">TDP càng cao chứng tỏ máy tiêu hao năng lượng nhiều hơn để bung tối đa hiệu năng.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- =================== TAB 2: LAPTOP CUSTOMIZER =================== -->
        <div v-else-if="activeTab === 'customizer'" class="laptop-customizer" key="customizer">
          <div class="grid-layout-custom">
            
            <!-- Left Column: Visual Customizer Canvas -->
            <div class="canvas-panel card-glass center-align">
              <!-- Visual Toggles -->
              <div class="canvas-toggles">
                <button 
                  @click="customizerView = 'lid'" 
                  :class="{ active: customizerView === 'lid' }"
                  class="toggle-btn"
                >
                  💻 Vỏ Máy (Lid Outer)
                </button>
                <button 
                  @click="customizerView = 'keyboard'" 
                  :class="{ active: customizerView === 'keyboard' }"
                  class="toggle-btn"
                >
                  ⌨️ Bàn Phím (LED Backlit)
                </button>
                <button 
                  @click="customizerView = 'internals'" 
                  :class="{ active: customizerView === 'internals' }"
                  class="toggle-btn"
                >
                  🔬 Mô Phỏng 3D Linh Kiện
                </button>
              </div>

              <!-- VIEW 1: OUTSIDE LAPTOP (MẶT A + STICKERS) -->
              <div v-if="customizerView === 'lid'" class="laptop-lid-container animate-fade-in">
                <div 
                  class="laptop-lid" 
                  :style="{ background: chassisColorGrad }"
                  ref="laptopLidRef"
                >
                  <!-- Glossy Metal Highlight -->
                  <div class="metal-shine"></div>
                  
                  <!-- VinaTech Logo Core Center -->
                  <div class="center-brand-logo">
                    <span class="logo-text">NextGen</span>
                  </div>

                  <!-- Dynamic Applied Stickers Layer -->
                  <div 
                    v-for="stk in appliedStickers" 
                    :key="stk.id"
                    class="applied-sticker"
                    :class="{ selected: selectedStickerId === stk.id }"
                    :style="{
                      left: stk.x + '%',
                      top: stk.y + '%',
                      transform: 'rotate(' + stk.rotate + 'deg) scale(' + stk.scale + ')',
                    }"
                    @mousedown.stop="startDrag($event, stk.id)"
                    @touchstart.stop="startDrag($event, stk.id)"
                  >
                    <span class="sticker-emoji-icon">{{ stk.icon }}</span>
                    <!-- Border helpers when selected -->
                    <div class="sticker-border-indicator" v-if="selectedStickerId === stk.id">
                      <span class="corner-dot dot-tl"></span>
                      <span class="corner-dot dot-tr"></span>
                      <span class="corner-dot dot-bl"></span>
                      <span class="corner-dot dot-br"></span>
                    </div>
                  </div>
                </div>
                <p class="interaction-tip">💡 Mẹo: Nhấn và kéo nhãn dán để di chuyển tự do trên vỏ máy!</p>
              </div>

              <!-- VIEW 2: KEYBOARD (MẶT C + RGB BACKLIGHT) -->
              <div v-else-if="customizerView === 'keyboard'" class="laptop-keyboard-container animate-fade-in">
                <div class="keyboard-chassis">
                  <!-- Touchpad area -->
                  <div class="keyboard-top-grill">
                    <span class="speaker-grill-left"></span>
                    <span class="power-btn" :style="{ boxShadow: '0 0 10px ' + ledGlowColor }"></span>
                    <span class="speaker-grill-right"></span>
                  </div>

                  <!-- Simulated Keyboard Keys Grid -->
                  <div class="keys-grid">
                    <!-- Row 1 -->
                    <div class="key key-small key-esc" :style="keyLedStyle">Esc</div>
                    <div class="key key-small" v-for="n in 12" :key="'f-'+n" :style="keyLedStyle">F{{ n }}</div>
                    <div class="key key-small key-delete" :style="keyLedStyle">Del</div>

                    <!-- Row 2 -->
                    <div class="key" :style="keyLedStyle">~</div>
                    <div class="key" v-for="n in 9" :key="'n-'+n" :style="keyLedStyle">{{ n }}</div>
                    <div class="key" :style="keyLedStyle">0</div>
                    <div class="key key-backspace" :style="keyLedStyle">Backspace</div>

                    <!-- Row 3 -->
                    <div class="key key-tab" :style="keyLedStyle">Tab</div>
                    <div class="key" :style="keyLedStyle">Q</div>
                    <div class="key key-game" :style="keyLedStyle">W</div>
                    <div class="key" :style="keyLedStyle">E</div>
                    <div class="key" :style="keyLedStyle">R</div>
                    <div class="key" :style="keyLedStyle">T</div>
                    <div class="key" :style="keyLedStyle">Y</div>
                    <div class="key" :style="keyLedStyle">U</div>
                    <div class="key" :style="keyLedStyle">I</div>
                    <div class="key" :style="keyLedStyle">O</div>
                    <div class="key" :style="keyLedStyle">P</div>
                    <div class="key key-brackets" :style="keyLedStyle">[ ]</div>
                    <div class="key key-slash" :style="keyLedStyle">\</div>

                    <!-- Row 4 -->
                    <div class="key key-caps" :style="keyLedStyle">Caps</div>
                    <div class="key key-game" :style="keyLedStyle">A</div>
                    <div class="key key-game" :style="keyLedStyle">S</div>
                    <div class="key key-game" :style="keyLedStyle">D</div>
                    <div class="key" :style="keyLedStyle">F</div>
                    <div class="key" :style="keyLedStyle">G</div>
                    <div class="key" :style="keyLedStyle">H</div>
                    <div class="key" :style="keyLedStyle">J</div>
                    <div class="key" :style="keyLedStyle">K</div>
                    <div class="key" :style="keyLedStyle">L</div>
                    <div class="key" :style="keyLedStyle">;</div>
                    <div class="key key-enter" :style="keyLedStyle">Enter</div>

                    <!-- Row 5 -->
                    <div class="key key-shift" :style="keyLedStyle">Shift</div>
                    <div class="key" :style="keyLedStyle">Z</div>
                    <div class="key" :style="keyLedStyle">X</div>
                    <div class="key" :style="keyLedStyle">C</div>
                    <div class="key" :style="keyLedStyle">V</div>
                    <div class="key" :style="keyLedStyle">B</div>
                    <div class="key" :style="keyLedStyle">N</div>
                    <div class="key" :style="keyLedStyle">M</div>
                    <div class="key" :style="keyLedStyle">,</div>
                    <div class="key" :style="keyLedStyle">.</div>
                    <div class="key" :style="keyLedStyle">/</div>
                    <div class="key key-shift-r" :style="keyLedStyle">Shift</div>

                    <!-- Row 6 -->
                    <div class="key key-ctrl" :style="keyLedStyle">Ctrl</div>
                    <div class="key key-win" :style="keyLedStyle">Win</div>
                    <div class="key key-alt" :style="keyLedStyle">Alt</div>
                    <div class="key key-space" :style="keyLedStyle">SPACE</div>
                    <div class="key key-alt" :style="keyLedStyle">Alt</div>
                    <div class="key key-ctrl" :style="keyLedStyle">Ctrl</div>
                    <div class="key key-arrow" :style="keyLedStyle">▲</div>
                    <div class="key key-arrow" :style="keyLedStyle">▼</div>
                  </div>

                  <!-- Large glass trackpad -->
                  <div class="trackpad-glass"></div>
                </div>
                <p class="interaction-tip">⌨️ Phía trên là mô phỏng bàn phím tích hợp đèn LED RGB sinh động!</p>
              </div>

              <!-- VIEW 3: 3D INTERNALS EXPLODED VIEW / SCHEMATIC OVERVIEW -->
              <div v-else-if="customizerView === 'internals'" class="laptop-internals-container animate-fade-in" :class="{ 'schematic-active': showSchematic }">
                
                <!-- SCHEMATIC 2D STATE -->
                <div v-if="showSchematic" class="schematic-view-container animate-fade-in">
                  <div class="schematic-image-wrapper" @click="triggerExplosion" title="Click để kích hoạt bung máy 3D!">
                    <img src="/schematic_laptop.png" alt="Sơ đồ laptop ngoài" class="schematic-img animate-pulse-gentle" />
                    
                    <!-- Pulsing center button to trigger 3D explosion -->
                    <div class="schematic-overlay-cta">
                      <span class="cta-pulse-ring"></span>
                      <span class="cta-text">💥 BẤM ĐỂ BUNG MÁY 3D</span>
                    </div>

                    <!-- Interactive pins on top of the image -->
                    <div 
                      v-for="part in schematicParts" 
                      :key="part.id" 
                      class="schematic-pin"
                      :style="{ left: part.x + '%', top: part.y + '%' }"
                      :class="{ active: activeSchematicPart === part.id }"
                      @mouseenter.stop="activeSchematicPart = part.id"
                      @mouseleave.stop="activeSchematicPart = null"
                      @click.stop="triggerExplosion"
                    >
                      <span class="pin-dot"></span>
                      <span class="pin-ripple"></span>
                      
                      <!-- Hover float tooltip -->
                      <div class="pin-tooltip card-glass">
                        <span class="tooltip-emoji">{{ part.emoji }}</span>
                        <span class="tooltip-name">{{ part.name }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="manual-rotation-instructions text-cyan">
                    💡 Rê chuột qua các điểm đỏ nhấp nháy để xem bộ phận ngoài, click vào ảnh để BUNG LỚP 3D khám phá linh kiện bên trong!
                  </div>
                </div>

                <!-- 3D INTERNAL EXPLODED VIEW STATE -->
                <div v-else class="scene-3d-wrapper-outer animate-fade-in">
                  <!-- Mode Selector Toggle between NextGen and MacBook Pro -->
                  <div class="model-3d-selector-row">
                    <button 
                      @click="selectedModel3D = 'nextgen'" 
                      :class="{ active: selectedModel3D === 'nextgen' }"
                      class="selector-3d-btn"
                    >
                      🚀 NextGen Elite (Bung Linh Kiện)
                    </button>
                    <button 
                      @click="selectedModel3D = 'macbook'" 
                      :class="{ active: selectedModel3D === 'macbook' }"
                      class="selector-3d-btn"
                    >
                      🍏 MacBook Pro (Ngoại Hình 3D)
                    </button>
                  </div>

                  <div 
                    class="scene-3d-wrapper"
                    @mousemove="handleMouseMove3D"
                    @mouseleave="handleMouseLeave3D"
                  >
                    <div 
                      class="scene-3d"
                      :style="{
                        transform: `rotateX(${explodedRotateX}deg) rotateY(${explodedRotateY}deg) rotateZ(${explodedRotateZ}deg)`
                      }"
                    >
                      <!-- ORIGINAL NextGen Gaming Laptop Exploded Layers -->
                      <template v-if="selectedModel3D === 'nextgen'">
                        <!-- Layer 1: Display Panel (Top) -->
                        <div 
                          class="layer-3d display-layer"
                          :class="{ dimmed: activePart && activePart !== 'display' }"
                          :style="{
                            transform: `translateZ(${explodedGap * 1.5}px)`
                          }"
                        >
                          <img src="/elite_display_panel.png" alt="Màn hình" />
                          
                          <!-- Hotspot for Display Screen -->
                          <div 
                            class="hotspot display-hotspot" 
                            :class="{ active: activePart === 'display' }"
                            @click="selectPart('display')"
                            @mouseenter="selectPart('display')"
                            title="Màn hình Ultra-WQHD 240Hz"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">SCREEN</span>
                          </div>
                          
                          <div class="layer-label">Màn Hình Ultra-WQHD 240Hz</div>
                        </div>

                        <!-- Layer 2: Keyboard Chassis (Middle Top) -->
                        <div 
                          class="layer-3d chassis-layer"
                          :class="{ dimmed: activePart && activePart !== 'chassis' }"
                          :style="{
                            transform: `translateZ(${explodedGap * 0.5}px)`
                          }"
                        >
                          <img src="/elite_chassis_cnc.png" alt="Khung sườn" />
                          
                          <!-- Hotspot for CNC Case -->
                          <div 
                            class="hotspot chassis-hotspot" 
                            :class="{ active: activePart === 'chassis' }"
                            @click="selectPart('chassis')"
                            @mouseenter="selectPart('chassis')"
                            title="Khung vỏ CNC Cường Lực"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">CASE</span>
                          </div>
                          
                          <div class="layer-label">Khung Vỏ CNC Cường Lực</div>
                        </div>

                        <!-- Layer 3: Motherboard Layer (Center) -->
                        <div 
                          class="layer-3d motherboard-layer"
                          :class="{ dimmed: activePart && !['cpu', 'gpu', 'ram', 'ssd'].includes(activePart) }"
                          :style="{
                            transform: `translateZ(0px)`
                          }"
                        >
                          <img src="/elite_motherboard.png" alt="Bo mạch chủ" />
                          
                          <!-- Hotspots on Motherboard with click and hover -->
                          <div 
                            class="hotspot cpu-hotspot" 
                            :class="{ active: activePart === 'cpu' }"
                            @click="selectPart('cpu')"
                            @mouseenter="selectPart('cpu')"
                            title="CPU Intel Core i9"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">CPU</span>
                          </div>

                          <div 
                            class="hotspot gpu-hotspot" 
                            :class="{ active: activePart === 'gpu' }"
                            @click="selectPart('gpu')"
                            @mouseenter="selectPart('gpu')"
                            title="GPU NVIDIA RTX 4090"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">GPU</span>
                          </div>

                          <div 
                            class="hotspot ram-hotspot" 
                            :class="{ active: activePart === 'ram' }"
                            @click="selectPart('ram')"
                            @mouseenter="selectPart('ram')"
                            title="RAM DDR5 Dual Channel"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">RAM</span>
                          </div>

                          <div 
                            class="hotspot ssd-hotspot" 
                            :class="{ active: activePart === 'ssd' }"
                            @click="selectPart('ssd')"
                            @mouseenter="selectPart('ssd')"
                            title="SSD PCIe Gen5 NVMe"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">SSD</span>
                          </div>
                          
                          <div class="layer-label">NextGen Motherboard V2</div>
                        </div>

                        <!-- Layer 4: Internal Cooling & Battery (Bottom) -->
                        <div 
                          class="layer-3d cooling-battery-layer"
                          :class="{ dimmed: activePart && !['cooling', 'battery'].includes(activePart) }"
                          :style="{
                            transform: `translateZ(-${explodedGap * 0.8}px)`
                          }"
                        >
                          <img src="/elite_laptop_parts.png" alt="Tản nhiệt và Pin" />
                          
                          <!-- Hotspots on Bottom Layer with click and hover -->
                          <div 
                            class="hotspot cooling-hotspot" 
                            :class="{ active: activePart === 'cooling' }"
                            @click="selectPart('cooling')"
                            @mouseenter="selectPart('cooling')"
                            title="Hệ thống tản nhiệt Dual-Turbo"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">COOLING</span>
                          </div>

                          <div 
                            class="hotspot battery-hotspot" 
                            :class="{ active: activePart === 'battery' }"
                            @click="selectPart('battery')"
                            @mouseenter="selectPart('battery')"
                            title="Pin VinaVolt 99.9Wh"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">BATTERY</span>
                          </div>

                          <div class="layer-label">Tản Nhiệt Dual-Turbo & Pin VinaVolt</div>
                        </div>
                      </template>

                      <!-- PREMIUM CSS 3D MacBook Pro Model -->
                      <div 
                        v-else-if="selectedModel3D === 'macbook'"
                        class="macbook-3d"
                        :class="[macbookColor, { 'dimmed-others': activeMacbookPart }]"
                      >
                        <!-- 1. MacBook Base (Thân máy) -->
                        <div 
                          class="macbook-base"
                          :class="{ highlighted: activeMacbookPart && ['keyboard', 'trackpad', 'speakers', 'ports', 'unibody'].includes(activeMacbookPart) }"
                        >
                          <!-- Metallic anodized surface with keyboard, speaker grates & trackpad -->
                          <div class="macbook-keyboard-area">
                            <!-- Simulated keys outline -->
                            <div class="macbook-keyboard-grid">
                              <span class="mb-key-row" v-for="r in 6" :key="'mb-row-'+r"></span>
                            </div>
                            <div class="macbook-trackpad-glass"></div>
                            <div class="macbook-speakers-grates left"></div>
                            <div class="macbook-speakers-grates right"></div>
                          </div>

                          <!-- Sleek metallic side edges & simulated ports -->
                          <div class="macbook-edge-side left">
                            <span class="mb-port ms3"></span>
                            <span class="mb-port tb4"></span>
                            <span class="mb-port tb4-2"></span>
                          </div>
                          <div class="macbook-edge-side right">
                            <span class="mb-port hdmi"></span>
                            <span class="mb-port tb4-3"></span>
                            <span class="mb-port audio"></span>
                          </div>

                          <!-- Interactive Hotspots on MacBook Base -->
                          <div 
                            class="hotspot mb-hotspot kbd-hotspot" 
                            :class="{ active: activeMacbookPart === 'keyboard' }"
                            @mouseenter="selectMacbookPart('keyboard')"
                            title="Magic Keyboard & Touch ID"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">KEYBOARD</span>
                          </div>

                          <div 
                            class="hotspot mb-hotspot trackpad-hotspot" 
                            :class="{ active: activeMacbookPart === 'trackpad' }"
                            @mouseenter="selectMacbookPart('trackpad')"
                            title="Force Touch Trackpad"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">TRACKPAD</span>
                          </div>

                          <div 
                            class="hotspot mb-hotspot speakers-hotspot" 
                            :class="{ active: activeMacbookPart === 'speakers' }"
                            @mouseenter="selectMacbookPart('speakers')"
                            title="Spatial Audio Speakers"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">AUDIO</span>
                          </div>

                          <div 
                            class="hotspot mb-hotspot ports-hotspot" 
                            :class="{ active: activeMacbookPart === 'ports' }"
                            @mouseenter="selectMacbookPart('ports')"
                            title="MagSafe 3 & Thunderbolt 4"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">I/O PORTS</span>
                          </div>

                          <div 
                            class="hotspot mb-hotspot unibody-hotspot" 
                            :class="{ active: activeMacbookPart === 'unibody' }"
                            @mouseenter="selectMacbookPart('unibody')"
                            title="Aluminium Unibody Shell"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">UNIBODY</span>
                          </div>
                        </div>

                        <!-- 2. MacBook Screen Hinge (Nắp gập màn hình xoay bản lề) -->
                        <div 
                          class="macbook-screen-hinge" 
                          :style="{ transform: `rotateX(-${macbookHingeAngle}deg)` }"
                          :class="{ highlighted: activeMacbookPart && ['screen', 'logo'].includes(activeMacbookPart) }"
                        >
                          <!-- Face 1: Screen Inner (Mặt trong màn hình hiển thị macOS) -->
                          <div class="macbook-screen-inner">
                            <div class="macbook-bezel">
                              <div class="macbook-notch-area">
                                <span class="mb-webcam"></span>
                              </div>
                              
                              <div class="macbook-display-panel" :class="[macbookWallpaper]">
                                <!-- macOS Menu Bar -->
                                <div class="macos-menubar">
                                  <span class="macos-apple-icon"></span>
                                  <span class="menu-bold">Finder</span>
                                  <span>File</span>
                                  <span>Edit</span>
                                  <span>View</span>
                                  <span>Go</span>
                                  <span>Window</span>
                                  <span class="menubar-right-status">🔋 100%  📶  22:13</span>
                                </div>

                                <!-- Desktop Center App mock -->
                                <div class="macos-desktop-center">
                                  <h4 class="macos-title-logo">MacBook Pro 16"</h4>
                                  <p class="macos-sub">Apple M3 Max Chip</p>
                                </div>

                                <!-- macOS Dock Bar -->
                                <div class="macos-dock-bar">
                                  <span class="dock-icon finder">🧭</span>
                                  <span class="dock-icon safari">🌐</span>
                                  <span class="dock-icon terminal">💻</span>
                                  <span class="dock-icon store">🛒</span>
                                  <span class="dock-icon settings">⚙️</span>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Face 2: Screen Outer (Mặt ngoài nắp máy chứa Apple Logo phát sáng) -->
                          <div class="macbook-screen-outer">
                            <div 
                              class="macbook-apple-logo-glow" 
                              :class="{ 'pulse-glow': activeMacbookPart === 'logo' }"
                            >
                              <svg class="apple-svg" viewBox="0 0 170 170" width="40" height="40">
                                <path fill="currentColor" d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.34.13-9.13-1.92-14.38-6.14-3.6-3.08-7.79-8.17-12.57-15.29-6.3-9.5-11.41-20.73-15.34-33.72-3.92-12.98-5.89-25.07-5.89-36.27 0-16.14 4.11-29.31 12.33-39.52 8.22-10.2 18.23-15.42 30.04-15.65 5.92 0 12.01 1.76 18.28 5.27 6.27 3.51 11.06 5.27 14.35 5.27 2.8 0 7.39-1.63 13.78-4.9 6.39-3.27 12.06-4.8 17.03-4.58 14.97.66 26.68 6.14 35.12 16.46-12.19 7.42-18.17 17.65-17.95 30.7 0.22 10.23 4.11 18.77 11.66 25.64 7.55 6.87 16.54 10.51 26.97 10.95-3.1 8.87-7.46 17.39-13.06 25.56zm-26.67-121.78c0 8.08-2.92 15.3-8.76 21.65-5.84 6.35-12.87 10.02-21.09 11.02.11-7.42 2.92-14.67 8.44-21.75 5.52-7.08 12.74-11.23 21.65-12.44 0.22 0.52 0.33 1.02 0.33 1.52z"/>
                              </svg>
                            </div>
                          </div>

                          <!-- Interactive Hotspots on MacBook Lid -->
                          <div 
                            class="hotspot mb-hotspot screen-hotspot" 
                            :class="{ active: activeMacbookPart === 'screen' }"
                            @mouseenter="selectMacbookPart('screen')"
                            title="Liquid Retina XDR Mini-LED Screen"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">RETINA XDR</span>
                          </div>

                          <div 
                            class="hotspot mb-hotspot logo-hotspot" 
                            :class="{ active: activeMacbookPart === 'logo' }"
                            @mouseenter="selectMacbookPart('logo')"
                            title="Glowing Apple Logo"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">APPLE LOGO</span>
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>

                  <div class="manual-rotation-instructions">
                    💡 Rê chuột qua các điểm nhấp nháy để quét chi tiết, di chuột tự do để xoay không gian 3D toàn cảnh!
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Controls Dashboard -->
            <div class="controls-panel card-glass">
              <div class="panel-header">
                <h3>BẢNG ĐIỀU KHIỂN SÁNG TẠO</h3>
              </div>

              <!-- CONTROLS FOR VIEW 1: COVER COLOR & STICKERS -->
              <div v-if="customizerView === 'lid'" class="lid-controls">
                
                <!-- 1. Color Selection -->
                <div class="control-section">
                  <h4 class="section-title">1. Chọn màu kim loại nắp máy:</h4>
                  <div class="chassis-color-grid">
                    <button 
                      v-for="color in chassisColors" 
                      :key="color.id"
                      @click="chassisColor = color.id"
                      :class="{ active: chassisColor === color.id }"
                      class="color-btn"
                      :style="{ background: color.grad }"
                      :title="color.name"
                    >
                      <span class="active-check" v-if="chassisColor === color.id">✓</span>
                    </button>
                  </div>
                  <p class="selected-meta">Vỏ màu hiện tại: <strong>{{ selectedChassisName }}</strong></p>
                </div>

                <!-- 2. Stickers Shelf -->
                <div class="control-section">
                  <h4 class="section-title">2. Thư viện Nhãn Dán (Stickers):</h4>
                  <div class="stickers-shelf">
                    <button 
                      v-for="stk in stickersLibrary" 
                      :key="stk.id"
                      @click="addSticker(stk)"
                      class="sticker-item"
                    >
                      <span class="stk-icon">{{ stk.icon }}</span>
                      <span class="stk-name">{{ stk.name }}</span>
                    </button>
                  </div>
                </div>

                <!-- 3. Edit Selected Sticker Properties -->
                <div class="control-section" v-if="selectedSticker">
                  <h4 class="section-title text-rainbow">3. Chỉnh sửa Sticker đang chọn:</h4>
                  <div class="selected-stk-badge">
                    <span>Nhãn đang chỉnh: <strong>{{ selectedSticker.icon }} {{ selectedSticker.name }}</strong></span>
                  </div>

                  <div class="sliders-grid">
                    <div class="slider-group">
                      <div class="slider-info">
                        <span>Xoay góc (Rotate)</span>
                        <span>{{ selectedSticker.rotate }}°</span>
                      </div>
                      <input 
                        type="range" 
                        min="0" 
                        max="360" 
                        v-model.number="selectedSticker.rotate" 
                        class="custom-range range-purple" 
                      />
                    </div>

                    <div class="slider-group">
                      <div class="slider-info">
                        <span>Kích thước (Scale)</span>
                        <span>{{ Math.round(selectedSticker.scale * 100) }}%</span>
                      </div>
                      <input 
                        type="range" 
                        min="0.5" 
                        max="2" 
                        step="0.1" 
                        v-model.number="selectedSticker.scale" 
                        class="custom-range range-purple" 
                      />
                    </div>
                  </div>

                  <div class="sticker-actions">
                    <button @click="deleteSelectedSticker" class="btn-stk-action btn-danger">
                      🗑️ Xóa Nhãn Dán này
                    </button>
                    <button @click="clearAllStickers" class="btn-stk-action btn-outline">
                      🧹 Xóa tất cả Sticker
                    </button>
                  </div>
                </div>
                <div class="control-section no-selection" v-else>
                  <p>💡 Vui lòng click chọn 1 Sticker trên nắp máy để xoay, thu phóng hoặc xóa.</p>
                </div>

              </div>

              <!-- CONTROLS FOR VIEW 2: RGB LED LIGHTING -->
              <div v-else-if="customizerView === 'keyboard'" class="keyboard-controls">
                
                <!-- 1. Select Led Backlit Mode -->
                <div class="control-section">
                  <h4 class="section-title">1. Chọn Hiệu ứng Đèn nền:</h4>
                  <div class="led-modes-grid">
                    <button 
                      v-for="mode in ledModes" 
                      :key="mode.id"
                      @click="ledMode = mode.id"
                      :class="{ active: ledMode === mode.id }"
                      class="led-mode-btn"
                    >
                      <span class="mode-emoji">{{ mode.emoji }}</span>
                      <div class="mode-meta">
                        <span class="mode-name">{{ mode.name }}</span>
                        <span class="mode-desc">{{ mode.desc }}</span>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- 2. Static Color Customizer Picker -->
                <div class="control-section" v-show="ledMode === 'static' || ledMode === 'breathe'">
                  <h4 class="section-title">2. Chọn màu LED Neon tùy chỉnh:</h4>
                  <div class="custom-color-picker-wrap">
                    <div class="color-picker-input-group">
                      <input 
                        type="color" 
                        v-model="customRgbColor" 
                        class="picker-box"
                      />
                      <div class="hex-info">
                        <span>Mã màu Hex:</span>
                        <input type="text" v-model="customRgbColor" class="hex-text-input" />
                      </div>
                    </div>

                    <!-- Preset Quick Colors -->
                    <div class="quick-colors">
                      <button 
                        v-for="qc in quickColors" 
                        :key="qc" 
                        @click="customRgbColor = qc"
                        class="quick-color-dot"
                        :style="{ background: qc }"
                      ></button>
                    </div>
                  </div>
                </div>

                <!-- Specs summary -->
                <div class="control-section spec-card-summary">
                  <h4>CẤU HÌNH TRẢI NGHIỆM ĐÃ CHỌN</h4>
                  <ul class="custom-specs-summary">
                    <li>💻 Màu vỏ máy: <strong>{{ selectedChassisName }}</strong></li>
                    <li>🎨 Nhãn trang trí: <strong>{{ appliedStickers.length }} Stickers đã dán</strong></li>
                    <li>⌨️ LED Bàn phím: <strong>{{ selectedLedModeLabel }}</strong></li>
                  </ul>
                  <button @click="handleSaveDesign" class="order-custom-btn btn-rainbow-glow">
                    🛒 Lưu Thiết Kế & Nhận 15 VinaCoins
                  </button>
                </div>

              </div>

              <!-- CONTROLS FOR VIEW 3: 3D INTERNALS EXPLODED VIEW -->
              <div v-else-if="customizerView === 'internals'" class="internals-controls">
                
                <!-- SCHEMATIC OVERVIEW CONTROLS -->
                <div v-if="showSchematic" class="schematic-controls animate-fade-in">
                  <!-- Section 1: Guide / Start CTA -->
                  <div class="control-section">
                    <h4 class="section-title">1. Sơ Đồ Thiết Kế Ngoài:</h4>
                    <p class="quest-p">Đây là bản vẽ mô phỏng các thành phần bên ngoài của Laptop. Di chuột qua các điểm nhấp nháy trên mô hình để chẩn đoán thông số.</p>
                    
                    <button @click="triggerExplosion" class="order-custom-btn btn-rainbow-glow w-full mt-4" style="margin-top: 15px; width: 100%;">
                      💥 KÍCH HOẠT BUNG MÁY 3D BÊN TRONG
                    </button>
                  </div>

                  <!-- Section 2: HUD inspector details -->
                  <div class="control-section parts-inspector">
                    <h4 class="section-title text-rainbow">2. Bộ Quét Bộ Phận Ngoài:</h4>
                    
                    <div class="parts-quick-select">
                      <button 
                        v-for="part in schematicParts" 
                        :key="part.id"
                        @click="activeSchematicPart = part.id"
                        @mouseenter="activeSchematicPart = part.id"
                        class="part-select-btn"
                        :class="{ active: activeSchematicPart === part.id }"
                      >
                        {{ part.emoji }} {{ part.name.split(' & ')[0].split(' ')[0] + ' ' + (part.name.split(' ')[1] || '') }}
                      </button>
                    </div>

                    <div v-if="currentSchematicPartData" class="part-details-card animate-fade-in" style="margin-top: 12px;">
                      <div class="part-details-header">
                        <span class="part-emoji">{{ currentSchematicPartData.emoji }}</span>
                        <div>
                          <h5>{{ currentSchematicPartData.name }}</h5>
                        </div>
                      </div>
                      <div class="part-description">
                        {{ currentSchematicPartData.desc }}
                      </div>
                      <div class="part-performance-bar text-cyan" style="font-size: 11px; margin-top: 8px; font-weight: bold;">
                        👉 Click để BUNG MÁY xem linh kiện bên trong!
                      </div>
                    </div>
                    <div v-else class="no-part-selected">
                      <p>🔍 Rê chuột hoặc bấm chọn linh kiện ngoài để thanh tra chi tiết thông số cấu tạo.</p>
                    </div>
                  </div>
                </div>

                <!-- 3D EXPLODED SETTINGS CONTROLS -->
                <div v-else class="internals-3d-controls animate-fade-in">
                  <!-- Button to go back to schematic -->
                  <div class="preset-angles-row mb-4" style="margin-bottom: 15px;">
                    <button @click="showSchematic = true" class="btn-preset-scene w-full" style="border-color: #06b6d4; background: rgba(6, 182, 212, 0.1); width: 100%;">
                      💻 Xem Sơ Đồ Thiết Kế Ngoài
                    </button>
                  </div>

                  <!-- 3D Scene Adjusters -->
                  <div class="control-section">
                    <h4 class="section-title">1. Góc Xoay & Độ Bung Linh Kiện:</h4>
                    
                    <div class="sliders-grid">
                      <div class="slider-group">
                        <div class="slider-info">
                          <span>Độ bung linh kiện (Exploded View)</span>
                          <span>{{ explodedGap }}px</span>
                        </div>
                        <input 
                          type="range" 
                          min="0" 
                          max="180" 
                          v-model.number="explodedGap" 
                          class="custom-range range-cyan" 
                        />
                      </div>

                      <div class="slider-group">
                        <div class="slider-info">
                          <span>Xoay Ngang (Rotate Y)</span>
                          <span>{{ explodedRotateY }}°</span>
                        </div>
                        <input 
                          type="range" 
                          min="-180" 
                          max="180" 
                          v-model.number="explodedRotateY" 
                          class="custom-range range-cyan" 
                        />
                      </div>

                      <div class="slider-group">
                        <div class="slider-info">
                          <span>Xoay Dọc (Rotate X)</span>
                          <span>{{ explodedRotateX }}°</span>
                        </div>
                        <input 
                          type="range" 
                          min="-60" 
                          max="60" 
                          v-model.number="explodedRotateX" 
                          class="custom-range range-cyan" 
                        />
                      </div>
                    </div>

                    <div class="preset-angles-row">
                      <button @click="reset3DScene" class="btn-preset-scene">🔄 Đặt Lại Scene</button>
                      <button @click="presetScene('exploded')" class="btn-preset-scene">💥 Bung Lớp 3D</button>
                      <button @click="presetScene('assembled')" class="btn-preset-scene">⚙️ Lắp Ráp Máy</button>
                    </div>
                  </div>

                  <!-- Component Details Inspector -->
                  <div class="control-section parts-inspector">
                    <h4 class="section-title text-rainbow">2. Thanh Tra Linh Kiện:</h4>
                    
                    <div class="parts-quick-select">
                      <button 
                        v-for="part in componentParts" 
                        :key="part.id"
                        @click="selectPart(part.id)"
                        @mouseenter="selectPart(part.id)"
                        class="part-select-btn"
                        :class="{ active: activePart === part.id }"
                      >
                        {{ part.emoji }} {{ part.name.split(' ')[0] + ' ' + (part.name.split(' ')[1] || '') }}
                      </button>
                    </div>

                    <!-- Details Display -->
                    <div v-if="selectedPartData" class="part-details-card animate-fade-in">
                      <div class="part-details-header">
                        <span class="part-emoji">{{ selectedPartData.emoji }}</span>
                        <div>
                          <h5>{{ selectedPartData.name }}</h5>
                          <span class="part-sub">{{ selectedPartData.sub }}</span>
                        </div>
                      </div>

                      <div class="part-description">
                        {{ selectedPartData.desc }}
                      </div>

                      <!-- Tech specs & rating system -->
                      <div class="part-specs-grid">
                        <div class="part-spec-item" v-for="(val, label) in selectedPartData.specs" :key="label">
                          <span class="spec-lbl">{{ label }}:</span>
                          <span class="spec-val cyan-text">{{ val }}</span>
                        </div>
                      </div>

                      <div class="part-performance-bar">
                        <div class="perf-title">⚡ Chỉ số hiệu năng (Power Score)</div>
                        <div class="perf-bar-track">
                          <div class="perf-bar-fill animate-width" :style="{ width: selectedPartData.score + '%', background: selectedPartData.color }"></div>
                        </div>
                        <div class="perf-score-desc">Cấp độ: <strong>{{ selectedPartData.score }} / 100</strong></div>
                      </div>
                    </div>
                    <div v-else class="no-part-selected">
                      <p>🔬 Rê chuột hoặc click chọn linh kiện trên hình ảnh 3D để quét chi tiết thông số kỹ thuật.</p>
                    </div>
                  </div>

                  <!-- Custom Coin boost integration -->
                  <div class="control-section spec-card-summary">
                    <h4>NHIỆM VỤ QUÉT LINH KIỆN</h4>
                    <p class="quest-p">Quét đầy đủ {{ componentParts.length }} linh kiện chính của laptop để hiểu rõ cơ chế vận hành và tích luỹ thêm phần thưởng!</p>
                    <div class="scanning-progress-wrap">
                      <span>Tiến trình quét: {{ scannedPartsCount }} / {{ componentParts.length }} linh kiện</span>
                      <div class="progress-track-mini">
                        <div class="progress-fill-mini" :style="{ width: (scannedPartsCount / componentParts.length) * 100 + '%' }"></div>
                      </div>
                    </div>
                    <button 
                      @click="claimScanningCoins" 
                      :disabled="scannedPartsCount < componentParts.length || hasClaimedScanReward"
                      class="order-custom-btn btn-rainbow-glow"
                      :class="{ disabled: scannedPartsCount < componentParts.length || hasClaimedScanReward }"
                    >
                      {{ hasClaimedScanReward ? '✔️ Đã nhận 25 VinaCoins' : (scannedPartsCount < componentParts.length ? '🔒 Hãy quét đủ ' + componentParts.length + ' linh kiện' : '🎁 Nhận ngay 25 VinaCoins!') }}
                    </button>
                  </div>
                </div>

              </div>

            </div>

          </div>
        </div>



        <!-- =================== TAB 4: AFFILIATE & QUEST GAMIFICATION =================== -->
        <div v-else class="gamification-section" key="gamification">
          <!-- Coins and wallet bar -->
          <div class="coins-wallet-bar card-glass">
            <div class="wallet-left">
              <span class="wallet-icon">💎</span>
              <div>
                <span class="wallet-label">VinaCoins Wallet của bạn:</span>
                <h2 class="coins-counter text-rainbow">{{ vinaCoins }} VinaCoins</h2>
              </div>
            </div>
            <div class="wallet-right">
              <p>Hoàn thành các nhiệm vụ hàng ngày để tích luỹ xu đổi Voucher giảm giá Laptop thực tế!</p>
            </div>
          </div>

          <div class="gamified-grid">
            
            <!-- Left panel: Ambassador Leaderboard -->
            <div class="leaderboard-panel card-glass">
              <div class="panel-header">
                <span class="leaderboard-icon">🏆</span>
                <h3>ĐẠI SỨ DANH VỌNG THÁNG 5</h3>
              </div>
              <p class="leaderboard-sub">Bảng xếp hạng Đại Sứ liên kết giới thiệu (Affiliate) xuất sắc nhất hệ thống.</p>

              <!-- Top 5 List -->
              <div class="leaderboard-list">
                <div 
                  v-for="(user, idx) in leaderboardUsers" 
                  :key="'user-'+idx"
                  class="leaderboard-row"
                  :class="{ 'first-place': idx === 0 }"
                >
                  <div class="rank-badge" :class="'rank-'+(idx+1)">
                    <span v-if="idx === 0">🥇</span>
                    <span v-else-if="idx === 1">🥈</span>
                    <span v-else-if="idx === 2">🥉</span>
                    <span v-else>{{ idx + 1 }}</span>
                  </div>
                  <img :src="user.avatar" :alt="user.name" class="user-avatar" />
                  <div class="user-meta">
                    <span class="user-name">{{ user.name }}</span>
                    <span class="user-title">{{ user.title }}</span>
                  </div>
                  <div class="user-points">
                    <span class="referrals-count">🗣️ {{ user.refs }} Lượt</span>
                    <span class="earned-com">{{ formatPrice(user.com) }}</span>
                  </div>
                </div>
              </div>

              <!-- Live payout ticker -->
              <div class="live-ticker-card">
                <div class="ticker-dot animate-pulse"></div>
                <div class="ticker-content-wrapper">
                  <span class="ticker-title">Thanh toán hoa hồng trực tuyến:</span>
                  <p class="ticker-text">{{ recentPayoutText }}</p>
                </div>
              </div>
            </div>

            <!-- Right panel: Quests and Rewards Shop -->
            <div class="quests-rewards-panel">
              
              <!-- 1. Daily Quests Shelf -->
              <div class="quests-box card-glass">
                <div class="panel-header">
                  <span class="quest-title-icon">⚡</span>
                  <h3>Nhiệm Vụ Hàng Ngày</h3>
                </div>
                
                <div class="quests-list">
                  <!-- Quest 1 -->
                  <div class="quest-row-card">
                    <div class="quest-body">
                      <span class="quest-check">🔗</span>
                      <div class="quest-info">
                        <h5>Chia sẻ đường dẫn Tiếp thị liên kết</h5>
                        <p>Sao chép link ref và chia sẻ lên MXH để nhận xu</p>
                      </div>
                    </div>
                    <button @click="completeShareQuest" class="quest-action-btn">
                      Copy Link (+20 💎)
                    </button>
                  </div>

                  <!-- Quest 2 -->
                  <div class="quest-row-card">
                    <div class="quest-body">
                      <span class="quest-check">📰</span>
                      <div class="quest-info">
                        <h5>Đọc bản tin Công nghệ NextGen mới</h5>
                        <p>Ghé thăm mục tin tức để tìm hiểu thêm xu hướng công nghệ</p>
                      </div>
                    </div>
                    <router-link to="/news" @click="completeNewsQuest" class="quest-action-btn link-btn">
                      Ghé xem (+10 💎)
                    </router-link>
                  </div>

                  <!-- Quest 3 -->
                  <div class="quest-row-card">
                    <div class="quest-body">
                      <span class="quest-check">🗣️</span>
                      <div class="quest-info">
                        <h5>Mời một người bạn ghé thăm VinaTech</h5>
                        <p>Nhập tên bạn bè để giới thiệu mua laptop lên đời</p>
                      </div>
                    </div>
                    <button @click="completeReferralQuest" class="quest-action-btn">
                      Giới thiệu (+50 💎)
                    </button>
                  </div>
                </div>
              </div>

              <!-- 2. Rewards Store Shop -->
              <div class="rewards-store card-glass">
                <div class="panel-header">
                  <span class="shop-icon">🛒</span>
                  <h3>VinaCoins Rewards Shop</h3>
                </div>
                <p class="rewards-sub">Đổi xu nhận Voucher trợ giá độc quyền mua laptop!</p>

                <div class="rewards-grid">
                  <div 
                    v-for="reward in rewardsShop" 
                    :key="reward.id" 
                    class="reward-card-item"
                  >
                    <span class="reward-emoji">{{ reward.emoji }}</span>
                    <h5>{{ reward.name }}</h5>
                    <p class="reward-cost">💰 Giá: {{ reward.cost }} VinaCoins</p>
                    <button 
                      @click="redeemReward(reward)" 
                      :disabled="vinaCoins < reward.cost"
                      class="redeem-btn"
                    >
                      Đổi ngay
                    </button>
                  </div>
                </div>

                <!-- Virtual Wallet Inventory for redeemed codes -->
                <div class="virtual-inventory" v-if="myRewards.length">
                  <h5>🎫 Ví Voucher Đã Đổi Của Bạn:</h5>
                  <div class="inventory-list">
                    <div v-for="item in myRewards" :key="item.id" class="inventory-row">
                      <div class="inv-left">
                        <span class="inv-name">{{ item.name }}</span>
                        <span class="inv-date">Đổi ngày: {{ item.date }}</span>
                      </div>
                      <span class="inv-code">{{ item.code }}</span>
                    </div>
                  </div>
                </div>
              </div>

            </div>

          </div>
        </div>

      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import api from '@/services/api';
import { storageUrl } from '@/services/urls';
import Swal from 'sweetalert2';

const formatPrice = (p) => new Intl.NumberFormat('vi-VN').format(p) + 'đ';

// Active Tab Layout
const activeTab = ref('versus'); // 'versus', 'customizer', 'gamification'
const customizerView = ref('lid'); // 'lid' or 'keyboard'

// ==========================================
// TAB 1 & 2 DATA: LAPTOPS, RADAR & CUSTOMIZER
// ==========================================
const realProducts = ref([]);
const isLoadingProducts = ref(false);
const selectedIdA = ref('preset-1');
const selectedIdB = ref('preset-2');

const presetLaptops = [
  {
    id: 'preset-1',
    name: 'NextGen Elite Beast Pro',
    fullName: 'NextGen Elite Beast Pro (RTX 4090 / Intel i9 / 64GB)',
    price: 89990000,
    cpu: 'Intel Core i9-14900HX (24 cores, 5.8GHz)',
    gpu: 'NVIDIA GeForce RTX 4090 16GB GDDR6',
    ram: '64GB DDR5 5600MHz Dual Channel',
    img: '/elite_motherboard.png',
    metrics: { cpu: 98, gpu: 99, battery: 55, portability: 45, cooling: 98 }
  },
  {
    id: 'preset-2',
    name: 'NextGen Elite Air M3',
    fullName: 'NextGen Elite Air (Apple M3 Max / 32GB / 1TB)',
    price: 64990000,
    cpu: 'Apple M3 Max (16-core CPU, 40-core GPU)',
    gpu: 'Apple M3 Max 40-Core GPU',
    ram: '32GB Unified Memory LPDDR5X',
    img: '/hero_3d_laptop.png',
    metrics: { cpu: 95, gpu: 85, battery: 98, portability: 95, cooling: 88 }
  },
  {
    id: 'preset-3',
    name: 'NextGen Elite Scholar Plus',
    fullName: 'NextGen Elite Scholar Plus (RTX 4060 / Ryzen 7 / 16GB)',
    price: 29990000,
    cpu: 'AMD Ryzen 7 8845HS (8 cores, 5.1GHz)',
    gpu: 'NVIDIA GeForce RTX 4060 8GB GDDR6',
    ram: '16GB LPDDR5X 7467MT/s',
    img: '/elite_workspace.png',
    metrics: { cpu: 85, gpu: 78, battery: 80, portability: 85, cooling: 82 }
  },
  {
    id: 'preset-4',
    name: 'NextGen Scholar Eco',
    fullName: 'NextGen Scholar Eco (Intel i5 / 16GB / 512GB)',
    price: 18490000,
    cpu: 'Intel Core i5-13420H (8 cores, 4.6GHz)',
    gpu: 'Intel Iris Xe Graphics',
    ram: '16GB LPDDR5 4800MHz',
    img: '/elite_unboxing.png',
    metrics: { cpu: 65, gpu: 45, battery: 75, portability: 90, cooling: 75 }
  }
];

const allLaptops = computed(() => {
  return [...presetLaptops, ...realProducts.value];
});

const laptopA = computed(() => {
  return allLaptops.value.find(l => l.id === selectedIdA.value) || presetLaptops[0];
});

const laptopB = computed(() => {
  return allLaptops.value.find(l => l.id === selectedIdB.value) || presetLaptops[1];
});

// Fetch products from API `/sanpham`
const fetchRealProducts = async () => {
  isLoadingProducts.value = true;
  try {
    const res = await api.get('/sanpham');
    const raw = Array.isArray(res.data) ? res.data : (res.data.data || []);
    
    realProducts.value = raw.map(p => {
      const vars = p.bien_the_san_pham || p.bienthe || [];
      const firstBt = vars[0] || {};
      
      let ram = '16GB DDR5';
      let cpu = 'Intel Core i7';
      let gpu = 'NVIDIA RTX 4060';
      
      try {
        const ts = typeof p.thong_so_ky_thuat === 'string' ? JSON.parse(p.thong_so_ky_thuat || '[]') : (p.thong_so_ky_thuat || []);
        if (Array.isArray(ts)) {
          const rObj = ts.find(t => t.ten === 'RAM' || t.ten === 'ram');
          const cObj = ts.find(t => t.ten === 'CPU' || t.ten === 'cpu');
          const gObj = ts.find(t => t.ten === 'GPU' || t.ten === 'gpu' || t.ten === 'Card đồ họa');
          if (rObj) ram = rObj.giatri;
          if (cObj) cpu = cObj.giatri;
          if (gObj) gpu = gObj.giatri;
        }
      } catch (e) {}

      const cpuLower = cpu.toLowerCase();
      const gpuLower = gpu.toLowerCase();
      const nameLower = p.tenSP.toLowerCase();

      let cpuScore = 75;
      if (cpuLower.includes('i9') || cpuLower.includes('r9') || cpuLower.includes('m3 max')) cpuScore = 95;
      else if (cpuLower.includes('i7') || cpuLower.includes('r7') || cpuLower.includes('m3 pro') || cpuLower.includes('ultra 7')) cpuScore = 85;

      let gpuScore = 50;
      if (gpuLower.includes('4090') || gpuLower.includes('4080')) gpuScore = 98;
      else if (gpuLower.includes('4070') || gpuLower.includes('4060')) gpuScore = 85;
      else if (gpuLower.includes('4050') || gpuLower.includes('rtx')) gpuScore = 75;

      let batteryScore = 70;
      if (nameLower.includes('macbook') || nameLower.includes('apple')) batteryScore = 95;
      else if (nameLower.includes('thin') || nameLower.includes('xps') || nameLower.includes('zenbook')) batteryScore = 80;

      let portScore = 70;
      if (p.khoiluong) {
        const kl = parseFloat(p.khoiluong);
        if (kl < 1.3) portScore = 95;
        else if (kl < 1.7) portScore = 80;
      }

      let coolScore = 75;
      if (nameLower.includes('rog') || nameLower.includes('legion') || nameLower.includes('gaming')) coolScore = 92;

      return {
        id: 'real-' + p.id_sanpham,
        name: p.tenSP,
        fullName: p.tenSP,
        price: firstBt.gia || p.gia_min || 20000000,
        cpu, gpu, gpu_spec: gpu, ram,
        img: firstBt.hinhanh ? storageUrl(firstBt.hinhanh) : (p.hinhanh ? storageUrl(p.hinhanh) : '/hero_3d_laptop.png'),
        metrics: { cpu: cpuScore, gpu: gpuScore, battery: batteryScore, portability: portScore, cooling: coolScore }
      };
    });
  } catch (e) {
    console.error('Lỗi khi fetch sản phẩm thực tế:', e);
  } finally {
    isLoadingProducts.value = false;
  }
};

// ==========================================
// RADAR CHART COORDINATES MATH Engine
// ==========================================
const getCoords = (score, index) => {
  const radius = (score / 100) * 100;
  const angle = -Math.PI / 2 + index * (2 * Math.PI / 5);
  const x = 150 + radius * Math.cos(angle);
  const y = 150 + radius * Math.sin(angle);
  return { x, y };
};

const getPolygonPoints = (laptop) => {
  if (!laptop || !laptop.metrics) return '';
  const m = laptop.metrics;
  const c0 = getCoords(m.cpu, 0);
  const c1 = getCoords(m.gpu, 1);
  const c2 = getCoords(m.battery, 2);
  const c3 = getCoords(m.portability, 3);
  const c4 = getCoords(m.cooling, 4);
  return `${c0.x},${c0.y} ${c1.x},${c1.y} ${c2.x},${c2.y} ${c3.x},${c3.y} ${c4.x},${c4.y}`;
};

const getGridPoints = (radiusScore) => {
  const c0 = getCoords(radiusScore, 0);
  const c1 = getCoords(radiusScore, 1);
  const c2 = getCoords(radiusScore, 2);
  const c3 = getCoords(radiusScore, 3);
  const c4 = getCoords(radiusScore, 4);
  return `${c0.x},${c0.y} ${c1.x},${c1.y} ${c2.x},${c2.y} ${c3.x},${c3.y} ${c4.x},${c4.y}`;
};

const getAxisEndCoords = (index) => {
  return getCoords(100, index);
};

// ==========================================
// GAME FPS & POWER SIMULATION Engine
// ==========================================
const selectedGameId = ref('wukong');
const graphicsSliderVal = ref(2); // 1 = low, 2 = medium, 3 = high

const games = [
  { id: 'wukong', name: 'Black Myth: Wukong', genre: 'Action RPG / Đồ họa 3D cực nặng', emoji: '🐒', base: 52 },
  { id: 'cyberpunk', name: 'Cyberpunk 2077', genre: 'Sci-fi Open World / Ray-Tracing', emoji: '🤖', base: 56 },
  { id: 'cs2', name: 'Counter-Strike 2', genre: 'FPS Esport / Tốc độ phản hồi', emoji: '🔫', base: 190 },
  { id: 'lol', name: 'League of Legends', genre: 'MOBA nhẹ / Ổn định tối đa', emoji: '⚔️', base: 310 }
];

const selectedGame = computed(() => {
  return games.find(g => g.id === selectedGameId.value) || games[0];
});

const graphicsSetting = computed(() => {
  const val = Number(graphicsSliderVal.value);
  if (val === 1) return 'low';
  if (val === 3) return 'high';
  return 'medium';
});

const graphicsSettingLabel = computed(() => {
  if (graphicsSetting.value === 'low') return 'Chất lượng Thấp (1080p - Hiệu năng cao)';
  if (graphicsSetting.value === 'high') return 'Cực cao Ultra (4K UHD - Đồ họa đỉnh cao)';
  return 'Trung bình (1440p QHD - Khuyên dùng)';
});

const calculateFPS = (laptop) => {
  if (!laptop || !laptop.metrics) return 0;
  const game = selectedGame.value;
  const isGpuBound = ['wukong', 'cyberpunk'].includes(game.id);
  const score = isGpuBound ? laptop.metrics.gpu : laptop.metrics.cpu;
  
  let fps = game.base * (score / 82);
  
  if (graphicsSetting.value === 'low') fps *= 1.45;
  if (graphicsSetting.value === 'high') fps *= 0.65;

  return Math.round(fps);
};

const fpsA = computed(() => calculateFPS(laptopA.value));
const fpsB = computed(() => calculateFPS(laptopB.value));

const calculateTemp = (laptop) => {
  if (!laptop || !laptop.metrics) return 0;
  let baseTemp = 86;
  const reduction = (laptop.metrics.cooling - 50) * 0.38;
  let temp = baseTemp - reduction;
  if (graphicsSetting.value === 'high') temp += 8;
  if (graphicsSetting.value === 'low') temp -= 5;
  return Math.round(temp);
};

const tempA = computed(() => calculateTemp(laptopA.value));
const tempB = computed(() => calculateTemp(laptopB.value));

const calculateTDP = (laptop) => {
  if (!laptop || !laptop.metrics) return 0;
  let tdp = 40;
  if (laptop.metrics.gpu > 90) tdp += 135;
  else if (laptop.metrics.gpu > 75) tdp += 75;
  else if (laptop.metrics.gpu > 50) tdp += 30;
  if (graphicsSetting.value === 'high') tdp *= 1.12;
  return Math.round(tdp);
};

const tdpA = computed(() => calculateTDP(laptopA.value));
const tdpB = computed(() => calculateTDP(laptopB.value));

const getPercentage = (val, max) => {
  if (!max) return 0;
  return Math.min(100, Math.max(0, Math.round((val / max) * 100)));
};

const getTempClass = (temp) => {
  if (temp > 85) return 'temp-danger';
  if (temp > 75) return 'temp-warning';
  return 'temp-safe';
};

const getTempBgClass = (temp) => {
  if (temp > 85) return 'red-bg';
  if (temp > 75) return 'orange-bg';
  return 'green-bg';
};

// ==========================================
// LAPTOP CUSTOMIZER & RGB BACKLIT Engine
// ==========================================
const chassisColor = ref('black');
const customRgbColor = ref('#2563eb');
const ledMode = ref('rainbow'); // 'rainbow', 'breathe', 'static', 'off'

// ==========================================
// 3D LAPTOP INTERNALS ENGINE
// ==========================================
const explodedGap = ref(100);
const explodedRotateX = ref(-20);
const explodedRotateY = ref(-35);
const explodedRotateZ = ref(0);
const activePart = ref(null);
const scannedParts = ref(JSON.parse(localStorage.getItem('scanned_parts') || '[]'));
const hasClaimedScanReward = ref(localStorage.getItem('scanned_parts_reward') === 'true');

// ==========================================
// 3D MACBOOK PRO ENGINE
// ==========================================
const selectedModel3D = ref('nextgen'); // 'nextgen' or 'macbook'
const macbookHingeAngle = ref(110); // 0 to 135
const macbookColor = ref('spacegray'); // 'spacegray' or 'silver'
const macbookWallpaper = ref('sequoia'); // 'sequoia', 'terminal', 'store'
const activeMacbookPart = ref(null);

const macbookParts = [
  {
    id: 'screen',
    name: 'Màn hình Liquid Retina XDR 16.2"',
    emoji: '🖥️',
    sub: 'Độ phân giải 3.4K, 1600 nits, ProMotion 120Hz',
    desc: 'Tấm nền Mini-LED Extreme Dynamic Range đỉnh cao, tỷ lệ tương phản 1.000.000:1, dải màu rộng P3 chuẩn studio, tần số quét thích ứng 120Hz mượt mà kinh ngạc.',
    specs: {
      'Độ phân giải': '3456 x 2234 (3.4K)',
      'Độ sáng tối đa': '1600 nits Peak HDR',
      'Công nghệ nền': 'Mini-LED 10,000 bóng LED',
      'Tần số quét': 'ProMotion 120Hz thích ứng'
    },
    score: 98,
    color: '#06b6d4'
  },
  {
    id: 'keyboard',
    name: 'Bàn phím Magic Keyboard & Touch ID',
    emoji: '⌨️',
    sub: 'Hành trình phím tối ưu, bảo mật sinh trắc học',
    desc: 'Phím gõ êm ái, ổn định tuyệt đối với cơ chế cắt kéo. Hàng phím chức năng full-size tiện lợi cùng cảm biến vân tay Touch ID siêu bảo mật tích hợp ở góc phải.',
    specs: {
      'Loại cơ cấu': 'Cơ cấu cắt kéo (Scissor-switch)',
      'Đèn nền': 'LED đơn trắng thông minh',
      'Bảo mật': 'Touch ID Secure Enclave'
    },
    score: 92,
    color: '#3b82f6'
  },
  {
    id: 'trackpad',
    name: 'Bàn rê Force Touch Trackpad',
    emoji: '🖱️',
    sub: 'Cảm ứng lực thông minh, phản hồi haptic',
    desc: 'Bàn rê cảm ứng lực lớn nhất thế giới, hỗ trợ nhận diện nhiều cấp độ lực nhấn và vô vàn cử chỉ Multi-Touch thông minh. Không có chi tiết chuyển động vật lý, giả lập lực nhấn bằng bộ rung haptic cực kỳ chính xác.',
    specs: {
      'Cơ chế hoạt động': 'Cảm biến lực + Taptic Engine',
      'Chất liệu bề mặt': 'Kính mờ Acid-etched Glass cao cấp',
      'Hỗ trợ cử chỉ': 'Multi-Touch & Force Click'
    },
    score: 95,
    color: '#a855f7'
  },
  {
    id: 'logo',
    name: 'Logo Táo khuyết mạ gương phát sáng',
    emoji: '🍏',
    sub: 'Biểu tượng đẳng cấp thiết kế Apple',
    desc: 'Biểu trưng Apple được gia công cắt laser chính xác bằng chất liệu kính tráng gương bóng bẩy chống xước, tạo điểm nhấn thẩm mỹ thanh lịch đặc trưng trên nắp nhôm.',
    specs: {
      'Chất liệu': 'Kính cường lực Mirror-polished',
      'Đặc điểm': 'Gia công chính xác, chống bám vân tay',
      'Hiệu ứng': 'Phát sáng neon đổi màu khi hover'
    },
    score: 90,
    color: '#f59e0b'
  },
  {
    id: 'speakers',
    name: 'Hệ thống 6 loa ngoài Spatial Audio',
    emoji: '🔊',
    sub: 'Loa trầm khử lực force-cancelling, âm thanh vòm',
    desc: 'Hệ thống âm thanh tốt nhất trên mọi chiếc laptop thế giới với 4 loa trầm khử lực triệt tiêu rung động và 2 loa bổng hiệu năng cao. Hỗ trợ Spatial Audio khi phát nhạc hoặc phim chuẩn Dolby Atmos.',
    specs: {
      'Cấu hình loa': '6 Loa Hi-Fi (4 Woofers + 2 Tweeters)',
      'Công nghệ': 'Spatial Audio & Dolby Atmos vòm',
      'Hỗ trợ mic': 'Cụm 3 micro định hướng chuẩn studio'
    },
    score: 97,
    color: '#ec4899'
  },
  {
    id: 'ports',
    name: 'Cổng MagSafe 3 & Thunderbolt 4',
    emoji: '🔌',
    sub: 'Kết nối đa năng, sạc hít nam châm an toàn',
    desc: 'Trang bị cổng sạc nam châm MagSafe 3 cực kỳ an toàn, tự động bung ra khi vấp cáp sạc. Đi kèm 3 cổng Thunderbolt 4 (USB-C) băng thông siêu tốc 40Gbps, khe đọc thẻ nhớ SDXC và cổng HDMI 2.1 xuất màn hình 8K.',
    specs: {
      'MagSafe 3': 'Hít nam châm sạc nhanh 140W',
      'Thunderbolt 4': '3 Cổng USB-C tốc độ 40Gbps',
      'Đầu ra hình ảnh': 'HDMI 2.1 & Đầu đọc thẻ nhớ SDXC'
    },
    score: 94,
    color: '#10b981'
  },
  {
    id: 'unibody',
    name: 'Vỏ nhôm nguyên khối Aluminium Unibody',
    emoji: '🛡️',
    sub: 'Nhôm tái chế 100% bảo vệ môi trường, siêu bền',
    desc: 'Khung sườn MacBook Pro được đúc và tiện từ một khối nhôm duy nhất siêu chịu lực. Vật liệu làm bằng nhôm tái chế 100% được gia công vi-anode chống bám mồ hôi và xước dăm hiệu quả.',
    specs: {
      'Chất liệu': '100% Nhôm Series 6000 tái chế',
      'Màu sắc': 'Space Gray (Xám) / Silver (Bạc)',
      'Trọng lượng': 'Chỉ 2.1 kg cho phiên bản 16.2"'
    },
    score: 96,
    color: '#6b7280'
  }
];

const selectedMacbookPartData = computed(() => {
  return macbookParts.find(p => p.id === activeMacbookPart.value);
});

const selectMacbookPart = (partId) => {
  activeMacbookPart.value = partId;
};

// Schematic Laptop Diagram States & Metadata
const showSchematic = ref(true);
const activeSchematicPart = ref(null);

const schematicParts = [
  {
    id: 'camera',
    name: 'Camera AI FHD & Cảm biến IR hồng ngoại',
    emoji: '📷',
    x: 48, y: 7,
    desc: 'Hệ thống camera độ phân giải cao kết hợp cảm biến hồng ngoại hỗ trợ bảo mật Windows Hello mở khóa bằng khuôn mặt 3D, tự động căn chỉnh khung hình AI.'
  },
  {
    id: 'screen',
    name: 'Màn Hình Ultra-OLED 16" 2.5K 240Hz',
    emoji: '🖥️',
    x: 60, y: 25,
    desc: 'Tấm nền OLED cao cấp thế hệ mới, độ phủ màu điện ảnh 100% DCI-P3, tần số quét 240Hz siêu mượt và độ sáng cực đại 600 nits cho trải nghiệm hình ảnh tuyệt mỹ.'
  },
  {
    id: 'top_panel',
    name: 'Khung Vỏ Nắp Trên Hợp Kim Nhôm',
    emoji: '💻',
    x: 35, y: 22,
    desc: 'Nắp vỏ nhôm dòng 6000 siêu nhẹ gia công CNC nguyên khối tinh xảo, xử lý anode mịn màng chống xước và tăng độ chịu lực tác động vật lý bên ngoài.'
  },
  {
    id: 'keyboard',
    name: 'Bàn Phím Cơ LED Backlit Cơ Học',
    emoji: '⌨️',
    x: 55, y: 55,
    desc: 'Lưới phím bấm cơ học thế hệ mới với hành trình phím 1.5mm phản hồi xúc giác tối ưu, trang bị đèn nền LED RGB độc lập lấp lánh.'
  },
  {
    id: 'touchpad',
    name: 'Bàn Rê Haptic Glass Touchpad',
    emoji: '🖱️',
    x: 63, y: 62,
    desc: 'Diện tích cực rộng, phủ kính cường lực mượt mà, tích hợp động cơ rung phản hồi lực Haptic chính xác thay cho cơ chế phím bấm vật lý truyền thống.'
  },
  {
    id: 'power_btn',
    name: 'Nút Nguồn Vân Tay 1 Chạm',
    emoji: '🔘',
    x: 31, y: 58,
    desc: 'Được trang bị cảm biến sinh trắc học vân tay siêu nhạy dưới nút nguồn giúp đăng nhập Windows an toàn chỉ trong một lần chạm.'
  },
  {
    id: 'charging_port',
    name: 'Cổng Sạc DC Siêu Tốc VinaCharge',
    emoji: '🔌',
    x: 27, y: 56,
    desc: 'Cổng sạc cấp nguồn công suất cao chuyên dụng, hỗ trợ công nghệ sạc siêu nhanh giúp hồi sinh 80% dung lượng pin trong vòng 45 phút sạc.'
  },
  {
    id: 'usb_ports',
    name: 'Cổng USB Siêu Tốc & Thunderbolt 4',
    emoji: '⚡',
    x: 34, y: 64,
    desc: 'Cổng giao tiếp đa năng băng thông siêu rộng 40Gbps hỗ trợ truyền dữ liệu, sạc nhanh Power Delivery và xuất ra thêm 2 màn hình rời 4K.'
  },
  {
    id: 'base_panel',
    name: 'Vỏ Đáy Hợp Kim Magie Siêu Bền',
    emoji: '🛡️',
    x: 77, y: 56,
    desc: 'Đế máy đúc nguyên khối Magie siêu nhẹ, thiết kế khe lưới tản nhiệt hình tổ ong tăng lưu lượng khí hút vào quạt tản nhiệt thêm 30%.'
  }
];

const currentSchematicPartData = computed(() => {
  return schematicParts.find(p => p.id === activeSchematicPart.value);
});

const triggerExplosion = () => {
  showSchematic.value = false;
  explodedGap.value = 140;
  explodedRotateX.value = -25;
  explodedRotateY.value = -45;
  activePart.value = 'cpu';
  if (!scannedParts.value.includes('cpu')) {
    scannedParts.value.push('cpu');
    localStorage.setItem('scanned_parts', JSON.stringify(scannedParts.value));
  }
};

const handleMouseMove3D = (event) => {
  const container = event.currentTarget;
  if (!container) return;
  const rect = container.getBoundingClientRect();
  const width = rect.width;
  const height = rect.height;
  
  // Mouse position relative to center of the container (-0.5 to 0.5)
  const relX = (event.clientX - rect.left) / width - 0.5;
  const relY = (event.clientY - rect.top) / height - 0.5;
  
  // Xoay ngang 180 độ (từ -180 đến 180 độ toàn cảnh)
  explodedRotateY.value = Math.round(relX * 360);
  
  // Xoay dọc (từ -45 đến 45 độ)
  explodedRotateX.value = Math.round(-relY * 90);
};

const handleMouseLeave3D = () => {
  // Smoothly return to standard premium diagonal view
  explodedRotateX.value = -25;
  explodedRotateY.value = -45;
};

const componentParts = [
  {
    id: 'display',
    name: 'Màn hình Ultra-WQHD 240Hz',
    emoji: '🖥️',
    sub: 'Tấm Nền OLED Điện Ảnh Thế Hệ Mới',
    desc: 'Tấm nền OLED cao cấp độ phủ màu 100% DCI-P3 siêu trung thực, hỗ trợ HDR600, tần số quét 240Hz lý tưởng cho game thủ FPS và nhà thiết kế chuyên nghiệp.',
    specs: {
      'Kích thước': '16 inches',
      'Độ Phân Giải': '2.5K WQHD (2560x1600)',
      'Tần Số Quét': '240Hz siêu mượt',
      'Công Nghệ': 'OLED HDR600'
    },
    score: 96,
    color: '#60a5fa'
  },
  {
    id: 'chassis',
    name: 'Khung Vỏ Kim Loại CNC',
    emoji: '💻',
    sub: 'Vỏ Nhôm Dòng 6000 Nguyên Khối Cường Lực',
    desc: 'Khung máy hợp kim nhôm-magie dòng 6000 tiện CNC siêu tỉ mỉ, tăng cường cấu trúc tản nhiệt thụ động, chịu lực và chống va đập tiêu chuẩn quân đội.',
    specs: {
      'Chất Liệu': 'Hợp Kim Nhôm-Magie 6000',
      'Độ Dày': 'Chỉ 15.9 mm',
      'Trọng Lượng vỏ': 'Gia cố chịu lực 25kg',
      'Quy Trình': 'Cắt CNC nguyên khối + Anode phủ mịn'
    },
    score: 93,
    color: '#94a3b8'
  },
  {
    id: 'cpu',
    name: 'CPU Intel Core i9-14900HX',
    emoji: '🧠',
    sub: 'Vi Xử Lý Siêu Cấp Hạng Raptor Lake',
    desc: 'Trang bị 24 nhân (8 nhân P-core hiệu năng cao & 16 nhân E-core tiết kiệm điện), xung nhịp turbo tối đa lên tới 5.8 GHz. Hỗ trợ Intel Thread Director tối ưu luồng chơi game và dựng hình 3D đỉnh cao.',
    specs: {
      'Số Nhân / Luồng': '24 Cores / 32 Threads',
      'Xung Nhịp': 'Xung turbo 5.8 GHz',
      'Bộ Nhớ Đệm': '36MB Smart Cache',
      'Công Suất tiêu thụ': '55W - 157W Turbo'
    },
    score: 98,
    color: '#06b6d4'
  },
  {
    id: 'gpu',
    name: 'GPU NVIDIA RTX 4090 Laptop',
    emoji: '🎮',
    sub: 'Quái Thú Đồ Họa Cận Cực Ada Lovelace',
    desc: 'Sở hữu 16GB VRAM GDDR6 siêu tốc, công nghệ DLSS 3.0 với Frame Generation tái tạo khung hình AI mượt mà gấp 4 lần. Hỗ trợ Full Ray-Tracing mang lại thế giới ảo lung linh chân thực.',
    specs: {
      'Nhân CUDA': '9728 Cores',
      'Bộ Nhớ VRAM': '16GB GDDR6 256-bit',
      'TDP Tối Đa': 'TGP 175W max',
      'Linh Kiện Ray-Tracing': 'Nhân RT thế hệ 3'
    },
    score: 99,
    color: '#ec4899'
  },
  {
    id: 'ram',
    name: 'RAM DDR5 Dual-Channel 64GB',
    emoji: '⚡',
    sub: 'Bộ Nhớ Siêu Băng Thông Cực Nhanh',
    desc: 'Hệ thống bộ nhớ dung lượng khủng 64GB DDR5 Dual Channel chạy ở bus cực cao 5600MHz, độ trễ cực thấp. Cho phép render video 4K song song chơi game AAA và chạy hàng chục tab Chrome không giật lag.',
    specs: {
      'Dung Lượng': '64GB (2 x 32GB)',
      'Chuẩn RAM': 'DDR5 SODIMM',
      'Tốc Độ Bus': '5600 MT/s',
      'Độ Trễ Latency': 'CL40 - Cực thấp'
    },
    score: 95,
    color: '#a855f7'
  },
  {
    id: 'ssd',
    name: 'SSD PCIe Gen 5 NVMe 2TB',
    emoji: '💾',
    sub: 'Ổ Cứng Siêu Tốc Thế Hệ Mới Nhất',
    desc: 'Ổ cứng thể rắn cao cấp nhất thế giới với chuẩn PCIe Gen 5.0 x4 mới nhất, mang lại tốc độ đọc ghi phi thường lên đến 12,000 MB/s. Load game nặng chỉ trong tích tắc, khởi động Windows chưa đầy 3 giây.',
    specs: {
      'Tốc Độ Đọc': 'Tới 12,400 MB/s',
      'Tốc Độ Ghi': 'Tới 11,800 MB/s',
      'Chuẩn Kết Nối': 'M.2 NVMe PCIe 5.0',
      'Độ Bền TBW': '1400 TBW cực trâu'
    },
    score: 97,
    color: '#10b981'
  },
  {
    id: 'cooling',
    name: 'Tản Nhiệt Dual-Turbo Liquid-Metal',
    emoji: '❄️',
    sub: 'Hệ Thống Làm Mát Keo Kim Liquid-Metal',
    desc: 'Sử dụng keo kim loại lỏng Thermal Grizzly thế hệ mới trên bề mặt CPU/GPU giúp hạ nhiệt độ đến 15°C so với keo truyền thống. Kết hợp 2 quạt cánh thép mỏng 0.1mm tăng lượng gió 35% mà không ồn.',
    specs: {
      'Số Quạt / Ống Đồng': '2 Quạt / 7 Ống dẫn nhiệt',
      'Lưu Lượng Gió': '32.5 CFM',
      'Độ Ồn Tối Đa': 'Dưới 42 dB',
      'Vật Liệu Tản': 'Keo kim loại lỏng + Đồng cánh sen'
    },
    score: 92,
    color: '#3b82f6'
  },
  {
    id: 'battery',
    name: 'Pin VinaVolt 99.9Wh 4-Cell',
    emoji: '🔋',
    sub: 'Nguồn Năng Lượng Đạt Chuẩn Hàng Không',
    desc: 'Dung lượng pin lớn nhất thế giới được phép mang lên máy bay (99.9 Watt-hour). Sử dụng lõi Lithium-Polymer cao cấp cho thời gian sử dụng văn phòng lên đến 10 tiếng liên tục. Tích hợp sạc nhanh VinaCharge 100W.',
    specs: {
      'Dung Lượng Wh': '99.9 Watt-hours',
      'Số Lõi Pin': '4-Cell Lithium-Polymer',
      'Công Suất Sạc': 'Sạc nhanh PD 100W',
      'Tuổi Thọ Vòng Đời': 'Hơn 1000 chu kỳ sạc'
    },
    score: 90,
    color: '#f59e0b'
  }
];

const selectedPartData = computed(() => {
  return componentParts.find(p => p.id === activePart.value);
});

const scannedPartsCount = computed(() => {
  return scannedParts.value.length;
});

const selectPart = (partId) => {
  activePart.value = partId;
  if (!scannedParts.value.includes(partId)) {
    scannedParts.value.push(partId);
    localStorage.setItem('scanned_parts', JSON.stringify(scannedParts.value));
  }
};

const reset3DScene = () => {
  explodedGap.value = 100;
  explodedRotateX.value = -20;
  explodedRotateY.value = -35;
  explodedRotateZ.value = 0;
};

const presetScene = (type) => {
  if (type === 'exploded') {
    explodedGap.value = 140;
    explodedRotateX.value = -25;
    explodedRotateY.value = -45;
  } else {
    explodedGap.value = 0;
    explodedRotateX.value = -15;
    explodedRotateY.value = -30;
  }
};

const claimScanningCoins = () => {
  if (scannedPartsCount.value < componentParts.length || hasClaimedScanReward.value) return;
  hasClaimedScanReward.value = true;
  localStorage.setItem('scanned_parts_reward', 'true');
  completeQuest(25, 'Khám phá và quét sơ đồ 3D linh kiện Laptop');
};

const chassisColors = [
  { id: 'black', name: 'Stealth Matte Black (Đen mờ)', grad: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)' },
  { id: 'silver', name: 'Liquid Titanium Silver (Bạc titan)', grad: 'linear-gradient(135deg, #cbd5e1 0%, #64748b 100%)' },
  { id: 'cyan', name: 'Quantum Cyber Cyan (Xanh Lazer)', grad: 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' },
  { id: 'purple', name: 'Obsidian Aurora Purple (Tím huyền ảo)', grad: 'linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%)' }
];

const selectedChassisName = computed(() => {
  const color = chassisColors.find(c => c.id === chassisColor.value);
  return color ? color.name : 'Stealth Matte Black';
});

const chassisColorGrad = computed(() => {
  const color = chassisColors.find(c => c.id === chassisColor.value);
  return color ? color.grad : 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)';
});

// Sticker Library
const stickersLibrary = [
  { id: 'stk-1', name: 'VinaTech Cyber', icon: '🌌' },
  { id: 'stk-2', name: 'Zero Bugs Coder', icon: '🐛' },
  { id: 'stk-3', name: 'RGB Gaming Beast', icon: '🦖' },
  { id: 'stk-4', name: 'Developer Coder', icon: '💻' },
  { id: 'stk-5', name: 'Space Astronaut', icon: '👨‍🚀' },
  { id: 'stk-6', name: 'Coffee Refueled', icon: '☕' }
];

const appliedStickers = ref([
  { id: 'default-stk', name: 'VinaTech Cyber', icon: '🌌', x: 42, y: 35, scale: 1.2, rotate: 15 }
]);
const selectedStickerId = ref('default-stk');

const selectedSticker = computed(() => {
  return appliedStickers.value.find(s => s.id === selectedStickerId.value);
});

const addSticker = (stk) => {
  const id = 'applied-' + Date.now();
  appliedStickers.value.push({
    id: id,
    name: stk.name,
    icon: stk.icon,
    x: 40,
    y: 40,
    scale: 1.0,
    rotate: 0
  });
  selectedStickerId.value = id;
};

const deleteSelectedSticker = () => {
  if (!selectedStickerId.value) return;
  appliedStickers.value = appliedStickers.value.filter(s => s.id !== selectedStickerId.value);
  selectedStickerId.value = appliedStickers.value.length ? appliedStickers.value[0].id : null;
};

const clearAllStickers = () => {
  appliedStickers.value = [];
  selectedStickerId.value = null;
};

// Sticker Drag mechanics
const activeDragStickerId = ref(null);
const laptopLidRef = ref(null);
let dragStartX = 0;
let dragStartY = 0;
let stickerStartX = 0;
let stickerStartY = 0;

const startDrag = (event, stickerId) => {
  activeDragStickerId.value = stickerId;
  selectedStickerId.value = stickerId;
  
  const stk = appliedStickers.value.find(s => s.id === stickerId);
  if (!stk) return;
  
  stickerStartX = stk.x;
  stickerStartY = stk.y;
  
  const clientX = event.touches ? event.touches[0].clientX : event.clientX;
  const clientY = event.touches ? event.touches[0].clientY : event.clientY;
  
  dragStartX = clientX;
  dragStartY = clientY;
  
  document.addEventListener('mousemove', handleDrag);
  document.addEventListener('mouseup', stopDrag);
  document.addEventListener('touchmove', handleDrag, { passive: false });
  document.addEventListener('touchend', stopDrag);
};

const handleDrag = (event) => {
  if (!activeDragStickerId.value) return;
  event.preventDefault();
  
  const clientX = event.touches ? event.touches[0].clientX : event.clientX;
  const clientY = event.touches ? event.touches[0].clientY : event.clientY;
  
  const deltaX = clientX - dragStartX;
  const deltaY = clientY - dragStartY;
  
  const containerWidth = laptopLidRef.value ? laptopLidRef.value.clientWidth : 350;
  const containerHeight = laptopLidRef.value ? laptopLidRef.value.clientHeight : 220;
  
  const stk = appliedStickers.value.find(s => s.id === activeDragStickerId.value);
  if (stk) {
    stk.x = Math.max(0, Math.min(88, stickerStartX + (deltaX / containerWidth) * 100));
    stk.y = Math.max(0, Math.min(84, stickerStartY + (deltaY / containerHeight) * 100));
  }
};

const stopDrag = () => {
  activeDragStickerId.value = null;
  document.removeEventListener('mousemove', handleDrag);
  document.removeEventListener('mouseup', stopDrag);
  document.removeEventListener('touchmove', handleDrag);
  document.removeEventListener('touchend', stopDrag);
};

// Keyboard Backlight Styles
const ledModes = [
  { id: 'rainbow', name: 'Rainbow Wave (Dải màu)', emoji: '🌈', desc: 'Chạy dải màu chuyển động lấp lánh liên tục' },
  { id: 'breathe', name: 'Breathing Glow (Nhịp thở)', emoji: '🫁', desc: 'Đèn LED nhấp nháy tỏa sáng theo chu kỳ nhịp thở' },
  { id: 'static', name: 'Static Neon (Màu tĩnh)', emoji: '💡', desc: 'Giữ sáng cố định theo màu sắc bạn pha trộn' },
  { id: 'off', name: 'Backlit Off (Tắt đèn)', emoji: '🌑', desc: 'Tắt toàn bộ hệ thống đèn nền bàn phím' }
];

const selectedLedModeLabel = computed(() => {
  const mode = ledModes.find(m => m.id === ledMode.value);
  return mode ? mode.name : 'Rainbow Wave';
});

const quickColors = ['#2563eb', '#ef4444', '#10b981', '#a855f7', '#f59e0b', '#ec4899', '#06b6d4', '#ffffff'];

const ledGlowColor = computed(() => {
  if (ledMode.value === 'off') return 'transparent';
  if (ledMode.value === 'rainbow') return '#3b82f6';
  return customRgbColor.value;
});

const keyLedStyle = computed(() => {
  if (ledMode.value === 'off') {
    return { borderColor: '#334155', color: '#475569', boxShadow: 'none', textShadow: 'none' };
  }

  if (ledMode.value === 'rainbow') {
    return { animation: 'ledRainbowWave 8s linear infinite', borderColor: 'rgba(255, 255, 255, 0.15)' };
  }

  if (ledMode.value === 'breathe') {
    return { animation: 'ledBreatheGlow 3s ease-in-out infinite', '--custom-color': customRgbColor.value, borderColor: 'rgba(255, 255, 255, 0.2)' };
  }

  return {
    borderColor: 'rgba(255, 255, 255, 0.2)',
    color: '#ffffff',
    boxShadow: `inset 0 0 4px ${customRgbColor.value}, 0 0 5px ${customRgbColor.value}`,
    textShadow: `0 0 3px ${customRgbColor.value}`
  };
});

// Trigger complete Quest 4 on saving custom design
const handleSaveDesign = () => {
  Swal.fire({
    title: 'Đã lưu thiết kế! 💻',
    text: 'Cấu hình thiết kế laptop độc quyền của bạn đã được ghi lại thành công.',
    icon: 'success',
    confirmButtonText: 'Đóng',
    confirmButtonColor: '#2563eb'
  });
  completeQuest(15, 'Trải nghiệm cá nhân hóa Laptop');
};





// ==========================================
// NEW FEATURE: TAB 4: AFFILIATE GAMIFICATION
// ==========================================
const getStoredCoins = () => {
  try {
    const val = localStorage.getItem('vina_coins');
    return val ? (parseInt(val, 10) || 30) : 30;
  } catch (e) {
    return 30;
  }
};

const getStoredRewards = () => {
  try {
    const val = localStorage.getItem('redeemed_rewards');
    return val ? JSON.parse(val) : [];
  } catch (e) {
    return [];
  }
};

const vinaCoins = ref(getStoredCoins());
const myRewards = ref(getStoredRewards());

const leaderboardUsers = [
  { name: 'Nguyễn Văn Hùng', title: 'Đại sứ Kim Cương', refs: 142, com: 42600000, avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Felix' },
  { name: 'Trần Thị Mai', title: 'Đại sứ Vàng', refs: 89, com: 26700000, avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Mia' },
  { name: 'Phạm Minh Tuấn', title: 'Đại sứ Vàng', refs: 74, com: 22200000, avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Jack' },
  { name: 'Lê Thanh Vy', title: 'Đại sứ Bạc', refs: 45, com: 13500000, avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Zoe' },
  { name: 'Hoàng Quốc Bảo', title: 'Đại sứ Bạc', refs: 38, com: 11400000, avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Buddy' }
];

const rewardsShop = [
  { id: 'rew-1', name: 'Voucher Trợ Giá 200,000đ mua Laptop', emoji: '🎫', cost: 100 },
  { id: 'rew-2', name: 'Mã Miễn Phí Vận Chuyển VVIP Toàn Quốc', emoji: '🚚', cost: 50 },
  { id: 'rew-3', name: 'Bộ 6 Sticker Kim Loại NextGen Độc Quyền', emoji: '🎁', cost: 25 }
];

// Ambassador Live Ticker simulator
const recentPayouts = [
  'Đại sứ Trần Thị Mai vừa nhận +450,000đ rút tiền hoa hồng liên kết!',
  'Đại sứ Lê Thanh Vy vừa ghi nhận đơn hàng mới phát sinh hoa hồng +350,000đ!',
  'Đại sứ Nguyễn Văn Hùng vừa nhận giải thưởng Top 1 Đại sứ liên kết xuất sắc +1,000,000đ!',
  'Cộng tác viên Phạm Minh Tuấn vừa chuyển thành công đơn hàng Laptop Gaming!'
];
const currentPayoutIndex = ref(0);
const recentPayoutText = computed(() => recentPayouts[currentPayoutIndex.value]);

let payoutInterval = null;
const rotatePayoutText = () => {
  currentPayoutIndex.value = (currentPayoutIndex.value + 1) % recentPayouts.length;
};

// Gamification Wallet Helpers
const completeQuest = (amount, questName) => {
  vinaCoins.value += amount;
  localStorage.setItem('vina_coins', vinaCoins.value.toString());
  
  Swal.fire({
    title: `+${amount} VinaCoins! 💎`,
    text: `Chúc mừng bạn đã hoàn thành nhiệm vụ: "${questName}"`,
    icon: 'success',
    confirmButtonColor: '#2563eb'
  });
};

const completeShareQuest = () => {
  const refLink = `${window.location.origin}/?ref=VINATECH-AMB`;
  navigator.clipboard.writeText(refLink).then(() => {
    Swal.fire({
      title: 'Đã sao chép link Ref! 🔗',
      text: 'Link tiếp thị đã được lưu vào khay nhớ tạm. Hãy dán chia sẻ lên Facebook/Zalo nhé!',
      icon: 'info',
      confirmButtonColor: '#2563eb'
    }).then(() => {
      completeQuest(20, 'Chia sẻ đường dẫn Tiếp thị liên kết');
    });
  });
};

const completeNewsQuest = () => {
  completeQuest(10, 'Tìm hiểu tin tức công nghệ');
};

const completeReferralQuest = () => {
  Swal.fire({
    title: 'Mời bạn bè ghé thăm 👥',
    text: 'Nhập họ tên người bạn muốn giới thiệu mua Laptop:',
    input: 'text',
    inputPlaceholder: 'Ví dụ: Nguyễn Văn A',
    showCancelButton: true,
    confirmButtonText: 'Gửi lời mời',
    confirmButtonColor: '#2563eb',
    cancelButtonText: 'Hủy'
  }).then((result) => {
    if (result.isConfirmed && result.value.trim()) {
      completeQuest(50, `Giới thiệu thành viên mới: ${result.value}`);
    }
  });
};

const redeemReward = (reward) => {
  if (vinaCoins.value < reward.cost) {
    Swal.fire({
      title: 'Không đủ VinaCoins! 💎',
      text: `Bạn cần thêm ${reward.cost - vinaCoins.value} xu để đổi phần quà này.`,
      icon: 'error',
      confirmButtonColor: '#ef4444'
    });
    return;
  }
  
  vinaCoins.value -= reward.cost;
  localStorage.setItem('vina_coins', vinaCoins.value.toString());
  
  // Create code and store in local inv
  const code = 'VNT-' + Math.random().toString(36).substr(2, 9).toUpperCase();
  const redeemedList = JSON.parse(localStorage.getItem('redeemed_rewards') || '[]');
  redeemedList.push({
    id: Date.now(),
    name: reward.name,
    code: code,
    date: new Date().toLocaleDateString('vi-VN')
  });
  localStorage.setItem('redeemed_rewards', JSON.stringify(redeemedList));
  myRewards.value = redeemedList;

  Swal.fire({
    title: 'Đổi Quà Thành Công! 🎉',
    html: `Bạn đã đổi thành công <strong>${reward.name}</strong>.<br/>Mã Voucher của bạn: <strong style="color: #2563eb; font-size: 18px; font-family: monospace;">${code}</strong>`,
    icon: 'success',
    confirmButtonColor: '#2563eb'
  });
};



// Lifecycle Hooks
onMounted(() => {
  fetchRealProducts();
  payoutInterval = setInterval(rotatePayoutText, 4500);
});

onUnmounted(() => {
  if (payoutInterval) clearInterval(payoutInterval);
});
</script>

<style scoped>
/* Page Layout styles */
.labs-page {
  position: relative;
  background-color: #080b11;
  color: #f1f5f9;
  min-height: 100vh;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  padding-bottom: 80px;
  overflow-x: hidden;
  z-index: 1;
}

/* Cyberpunk Animated Background Elements */
.cyber-grid-bg {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-image: 
    linear-gradient(rgba(8, 11, 17, 0.95), rgba(8, 11, 17, 0.95)),
    linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
  background-size: 100% 100%, 45px 45px, 45px 45px;
  opacity: 0.85;
  z-index: -2;
  pointer-events: none;
}
.cyber-scanline {
  position: fixed;
  top: 0; left: 0; width: 100vw; height: 3px;
  background: linear-gradient(to bottom, rgba(37, 99, 235, 0.06), transparent);
  animation: scanlineMove 12s linear infinite;
  z-index: -1;
  pointer-events: none;
}
@keyframes scanlineMove {
  0% { transform: translateY(-100px); }
  100% { transform: translateY(100vh); }
}
.ambient-glow {
  position: fixed;
  width: 60vw;
  height: 60vw;
  border-radius: 50%;
  filter: blur(160px);
  opacity: 0.15;
  z-index: -2;
  pointer-events: none;
}
.ambient-glow.cyan {
  top: -15%; right: -15%;
  background: radial-gradient(circle, rgba(6, 182, 212, 0.25) 0%, transparent 70%);
}
.ambient-glow.purple {
  bottom: -15%; left: -15%;
  background: radial-gradient(circle, rgba(124, 58, 237, 0.22) 0%, transparent 70%);
}

/* 1. HERO HEADER AREA */
.labs-hero {
  position: relative;
  background: radial-gradient(circle at top right, rgba(37, 99, 235, 0.18) 0%, rgba(15, 23, 42, 0) 60%),
              radial-gradient(circle at bottom left, rgba(124, 58, 237, 0.15) 0%, rgba(11, 15, 25, 0) 50%);
  padding: 80px 0 40px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  text-align: center;
}

.hero-content {
  max-width: 800px;
  margin: 0 auto;
  position: relative;
  z-index: 2;
  padding: 0 20px;
}

.badge-label {
  display: inline-block;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.2) 0%, rgba(124, 58, 237, 0.2) 100%);
  border: 1px solid rgba(37, 99, 235, 0.35);
  color: #60a5fa;
  padding: 6px 14px;
  border-radius: 30px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 20px;
  box-shadow: 0 0 15px rgba(37, 99, 235, 0.1);
}

.labs-hero h1 {
  font-family: 'Outfit', sans-serif;
  font-size: 42px;
  font-weight: 800;
  margin: 0 0 15px 0;
  background: linear-gradient(135deg, #ffffff 30%, #93c5fd 60%, #c084fc 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: -0.5px;
}

.labs-hero p {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 35px;
}

/* 2. TABS CONTAINER */
.tabs-container {
  display: inline-flex;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 6px;
  border-radius: 40px;
  gap: 5px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
  flex-wrap: wrap;
  justify-content: center;
}

.tab-btn {
  background: transparent;
  color: #94a3b8;
  border: none;
  padding: 10px 22px;
  border-radius: 35px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.tab-btn.active {
  background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
  color: white;
  box-shadow: 0 0 20px rgba(124, 58, 237, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.tab-btn:hover:not(.active) {
  color: white;
  background: rgba(255, 255, 255, 0.05);
}

/* Glassmorphism Cards */
.card-glass {
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(25px);
  -webkit-backdrop-filter: blur(25px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.35), 
              inset 0 1px 1px rgba(255, 255, 255, 0.08);
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
  overflow: hidden;
}

.card-glass::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; height: 1px;
  background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.4), transparent);
  opacity: 0;
  transition: opacity 0.4s;
}

.card-glass:hover::before {
  opacity: 1;
}

.card-glass:hover {
  transform: translateY(-4px);
  border-color: rgba(37, 99, 235, 0.3);
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.5), 
              0 0 25px rgba(37, 99, 235, 0.15);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.main-content {
  margin-top: 40px;
}

/* =================== TAB 1: VERSUS GRID =================== */
.grid-layout {
  display: grid;
  grid-template-columns: 320px 1fr 320px;
  gap: 25px;
  align-items: stretch;
}

.control-panel {
  padding: 25px;
  display: flex;
  flex-direction: column;
}

.panel-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  padding-bottom: 12px;
}

.dot-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.dot-indicator.blue {
  background-color: #06b6d4;
  box-shadow: 0 0 10px #06b6d4;
}

.dot-indicator.pink {
  background-color: #ec4899;
  box-shadow: 0 0 10px #ec4899;
}

.panel-header h3 {
  font-size: 15px;
  font-weight: 700;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #e2e8f0;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 13px;
  color: #94a3b8;
  margin-bottom: 8px;
}

.select-wrapper {
  position: relative;
}

.custom-select {
  width: 100%;
  background: rgba(15, 23, 42, 0.85);
  color: #f1f5f9;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  padding: 10px 15px;
  font-size: 14px;
  outline: none;
  cursor: pointer;
  appearance: none;
  transition: all 0.3s;
}

.custom-select:focus {
  border-color: #2563eb;
  box-shadow: 0 0 10px rgba(37, 99, 235, 0.25);
}

.select-wrapper::after {
  content: '▼';
  font-size: 10px;
  color: #94a3b8;
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}

.selected-product-card {
  margin-top: auto;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding-top: 20px;
}

.product-img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 12px;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.06);
  margin-bottom: 15px;
}

.product-info h4 {
  font-size: 16px;
  font-weight: 700;
  margin: 0 0 6px 0;
  color: #ffffff;
}

.product-info .price {
  font-size: 17px;
  font-weight: 800;
  color: #ef4444;
  margin: 0 0 15px 0;
}

.specs-list {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 13px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.specs-list li {
  line-height: 1.4;
  color: #cbd5e1;
}

.spec-label {
  color: #94a3b8;
  font-weight: 600;
}

/* RADAR MIDDLE PANEL */
.chart-panel {
  padding: 25px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.center-head h3 {
  background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 800;
}

.radar-container {
  width: 100%;
  max-width: 320px;
  margin: 15px auto;
}

.radar-svg {
  width: 100%;
  height: auto;
  overflow: visible;
}

.grid-polygon {
  fill: none;
  stroke: rgba(255, 255, 255, 0.04);
  stroke-width: 1px;
}

.grid-circle {
  fill: none;
  stroke: rgba(255, 255, 255, 0.05);
  stroke-width: 0.8px;
  stroke-dasharray: 2, 2;
}

.axis-line {
  stroke: rgba(255, 255, 255, 0.09);
  stroke-width: 1px;
}

.grid-text {
  fill: #475569;
  font-size: 7px;
  font-weight: 500;
  text-anchor: middle;
}

.axis-label {
  fill: #94a3b8;
  font-size: 8px;
  font-weight: 700;
}

.radar-poly {
  stroke-width: 2.2px;
  fill-opacity: 0.22;
  transition: points 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.poly-a {
  stroke: #06b6d4;
  fill: rgba(6, 182, 212, 0.25);
  filter: drop-shadow(0 0 5px rgba(6, 182, 212, 0.3));
}

.poly-b {
  stroke: #ec4899;
  fill: rgba(236, 72, 153, 0.25);
  filter: drop-shadow(0 0 5px rgba(236, 72, 153, 0.3));
}

.radar-legends {
  display: flex;
  gap: 20px;
  margin-top: 15px;
  flex-wrap: wrap;
  justify-content: center;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #cbd5e1;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 4px;
}

.legend-color.color-a {
  background-color: #06b6d4;
  box-shadow: 0 0 5px #06b6d4;
}

.legend-color.color-b {
  background-color: #ec4899;
  box-shadow: 0 0 5px #ec4899;
}

/* GAME SIMULATION Bottom section */
.simulation-section {
  margin-top: 30px;
  padding: 30px;
}

.simulation-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 25px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  padding-bottom: 15px;
}

.sim-icon {
  font-size: 32px;
}

.simulation-header h3 {
  font-size: 19px;
  font-weight: 700;
  margin: 0;
  color: #ffffff;
}

.simulation-header p {
  margin: 5px 0 0;
  font-size: 13px;
  color: #94a3b8;
}

.sim-grid {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 30px;
}

.section-sublabel {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #94a3b8;
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.games-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.game-card {
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 12px 18px;
  display: flex;
  align-items: center;
  gap: 15px;
  cursor: pointer;
  text-align: left;
  transition: all 0.3s;
}

.game-card:hover {
  background: rgba(255, 255, 255, 0.03);
  border-color: rgba(255, 255, 255, 0.15);
}

.game-card.active {
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.12) 0%, rgba(124, 58, 237, 0.12) 100%);
  border-color: #2563eb;
  box-shadow: 0 0 15px rgba(37, 99, 235, 0.15);
}

.game-emoji {
  font-size: 24px;
}

.game-meta {
  display: flex;
  flex-direction: column;
}

.game-name {
  font-size: 14px;
  font-weight: 700;
  color: white;
}

.game-genre {
  font-size: 11px;
  color: #64748b;
  margin-top: 2px;
}

.graphics-selector {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.slider-wrapper {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 15px;
  padding: 20px;
}

.setting-labels {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 10px;
}

.setting-labels span.active {
  color: #60a5fa;
  text-shadow: 0 0 5px rgba(96, 165, 250, 0.3);
}

.custom-range {
  width: 100%;
  height: 6px;
  background: #1e293b;
  border-radius: 5px;
  outline: none;
  cursor: pointer;
  accent-color: #2563eb;
}

.setting-description {
  margin-top: 15px;
  font-size: 13px;
  color: #cbd5e1;
  text-align: center;
}

.comparison-results {
  margin-top: 30px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding-top: 30px;
}

.versus-title-middle {
  text-align: center;
  font-family: 'Outfit', sans-serif;
  font-weight: 800;
  font-size: 13px;
  letter-spacing: 2px;
  color: #475569;
  margin-bottom: 25px;
}

.results-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 25px;
}

.stat-compare-card {
  background: rgba(15, 23, 42, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 15px;
  padding: 20px;
}

.stat-compare-title {
  font-size: 14px;
  font-weight: 700;
  color: #e2e8f0;
  margin-bottom: 18px;
}

.bar-wrapper {
  margin-bottom: 15px;
}

.bar-info {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  margin-bottom: 6px;
  color: #94a3b8;
}

.bar-info .value {
  font-weight: 700;
}

.cyan-text { color: #06b6d4; }
.pink-text { color: #ec4899; }

.bar-track {
  height: 10px;
  background: #060b13;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.6);
}

.bar-fill {
  height: 100%;
  border-radius: 20px;
  transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.cyan-bg { 
  background: linear-gradient(90deg, #0891b2 0%, #06b6d4 100%); 
  box-shadow: 0 0 12px rgba(6, 182, 212, 0.8); 
}
.pink-bg { 
  background: linear-gradient(90deg, #db2777 0%, #ec4899 100%); 
  box-shadow: 0 0 12px rgba(236, 72, 153, 0.8); 
}

.temp-danger { color: #f43f5e; text-shadow: 0 0 6px rgba(244, 63, 94, 0.4); }
.temp-warning { color: #fb923c; text-shadow: 0 0 6px rgba(251, 146, 60, 0.4); }
.temp-safe { color: #10b981; text-shadow: 0 0 6px rgba(16, 185, 129, 0.4); }

.red-bg { 
  background: linear-gradient(90deg, #e11d48 0%, #f43f5e 100%); 
  box-shadow: 0 0 12px rgba(244, 63, 94, 0.8); 
}
.orange-bg { 
  background: linear-gradient(90deg, #ea580c 0%, #fb923c 100%); 
  box-shadow: 0 0 12px rgba(251, 146, 60, 0.8); 
}
.green-bg { 
  background: linear-gradient(90deg, #059669 0%, #10b981 100%); 
  box-shadow: 0 0 12px rgba(16, 185, 129, 0.8); 
}

.stat-summary {
  font-size: 11px;
  color: #64748b;
  line-height: 1.4;
  margin: 12px 0 0 0;
}

/* =================== TAB 2: LAPTOP CUSTOMIZER =================== */
.grid-layout-custom {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 30px;
  align-items: stretch;
}

.canvas-panel {
  padding: 30px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.canvas-toggles {
  display: inline-flex;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 5px;
  border-radius: 12px;
  margin-bottom: 30px;
  gap: 5px;
}

.toggle-btn {
  background: transparent;
  color: #94a3b8;
  border: none;
  padding: 8px 18px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.toggle-btn.active {
  background: rgba(255, 255, 255, 0.08);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.laptop-lid-container {
  width: 100%;
  max-width: 500px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.laptop-lid {
  width: 100%;
  aspect-ratio: 1.5;
  border-radius: 15px;
  border: 3px solid #334155;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5), inset 0 0 10px rgba(255, 255, 255, 0.1);
  position: relative;
  overflow: hidden;
  transition: background 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: default;
}

.metal-shine {
  position: absolute;
  top: 0;
  left: 0;
  width: 150%;
  height: 100%;
  background: linear-gradient(120deg, rgba(255, 255, 255, 0) 30%, rgba(255, 255, 255, 0.04) 40%, rgba(255, 255, 255, 0.04) 45%, rgba(255, 255, 255, 0) 55%);
  pointer-events: none;
}

.center-brand-logo {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  opacity: 0.15;
  pointer-events: none;
}

.logo-text {
  font-family: 'Outfit', sans-serif;
  font-size: 32px;
  font-weight: 900;
  letter-spacing: -1px;
  color: white;
}

.applied-sticker {
  position: absolute;
  cursor: grab;
  user-select: none;
  touch-action: none;
  padding: 8px;
  border: 1px solid transparent;
  z-index: 10;
}

.applied-sticker:active {
  cursor: grabbing;
}

.applied-sticker.selected {
  border: 1px dashed rgba(168, 85, 247, 0.5);
  background: rgba(168, 85, 247, 0.05);
  border-radius: 4px;
}

.sticker-emoji-icon {
  font-size: 38px;
  display: block;
  filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3));
}

.sticker-border-indicator {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.corner-dot {
  width: 5px;
  height: 5px;
  background-color: #a855f7;
  border-radius: 50%;
  position: absolute;
}

.dot-tl { top: -2px; left: -2px; }
.dot-tr { top: -2px; right: -2px; }
.dot-bl { bottom: -2px; left: -2px; }
.dot-br { bottom: -2px; right: -2px; }

.interaction-tip {
  margin-top: 25px;
  font-size: 13px;
  color: #64748b;
  text-align: center;
}

.laptop-keyboard-container {
  width: 100%;
  max-width: 720px;
  display: flex;
  justify-content: center;
  padding: 0 10px;
}

.keyboard-chassis {
  background: #0f172a;
  border: 3px solid #334155;
  border-radius: 12px;
  padding: 15px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  box-sizing: border-box;
}

.keyboard-top-grill {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding: 0 10px;
}

.speaker-grill-left, .speaker-grill-right {
  width: 80px;
  height: 4px;
  background: repeating-linear-gradient(90deg, #1e293b, #1e293b 2px, transparent 2px, transparent 4px);
  opacity: 0.6;
}

.power-btn {
  width: 25px;
  height: 4px;
  background: #475569;
  border-radius: 10px;
  transition: all 0.3s;
}

.keys-grid {
  display: grid;
  /* fixed column widths provide consistent alignment across rows */
  grid-template-columns: repeat(15, 44px);
  gap: 8px;
  width: fit-content;
  max-width: 100%;
  margin: 0 auto; /* center the whole grid inside chassis */
  justify-content: center;
  background: #090d16;
  border-radius: 8px;
  padding: 12px 10px;
  border: 1px solid #1e293b;
  box-sizing: border-box;
}

.key {
  grid-column: span 1;
  width: 44px;
  height: 44px;
  background: #111827;
  border: 1px solid #334155;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  cursor: pointer;
  user-select: none;
  box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.5);
  transition: all 0.18s ease;
  padding: 4px;
  box-sizing: border-box;
}

.key:hover {
  background: #1f2937;
}

.key-small {
  grid-column: span 2;
  width: 84px;
  height: 36px;
  font-size: 10px;
}

.key-esc { grid-column: span 1; }
.key-delete { grid-column: span 1; }
.key-backspace { grid-column: span 3; aspect-ratio: unset; }
.key-tab { grid-column: span 2; aspect-ratio: unset; }
.key-brackets { grid-column: span 2; aspect-ratio: unset; }
.key-slash { grid-column: span 1; }
.key-caps { grid-column: span 2; aspect-ratio: unset; }
.key-enter { grid-column: span 3; aspect-ratio: unset; }
.key-shift { grid-column: span 3; aspect-ratio: unset; }
.key-shift-r { grid-column: span 3; aspect-ratio: unset; }
.key-ctrl { grid-column: span 2; aspect-ratio: unset; }
.key-win { grid-column: span 1; }
.key-alt { grid-column: span 2; aspect-ratio: unset; }
.key-space { grid-column: span 6; aspect-ratio: unset; font-size: 9px; font-weight: 800; letter-spacing: 2px; }
.key-arrow { grid-column: span 1; }

.key-game {
  font-weight: 900;
  border-width: 1.5px;
}

.trackpad-glass {
  width: 150px;
  height: 65px;
  background: rgba(255, 255, 255, 0.02);
  border: 1.5px solid rgba(255, 255, 255, 0.07);
  border-radius: 8px;
  margin: 15px auto 0;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
}

@keyframes ledRainbowWave {
  0%, 100% {
    box-shadow: inset 0 0 3px rgba(37, 99, 235, 0.8), 0 0 5px rgba(37, 99, 235, 0.6);
    text-shadow: 0 0 2px #2563eb;
    color: #93c5fd;
  }
  20% {
    box-shadow: inset 0 0 3px rgba(236, 72, 153, 0.8), 0 0 5px rgba(236, 72, 153, 0.6);
    text-shadow: 0 0 2px #ec4899;
    color: #fbcfe8;
  }
  40% {
    box-shadow: inset 0 0 3px rgba(124, 58, 237, 0.8), 0 0 5px rgba(124, 58, 237, 0.6);
    text-shadow: 0 0 2px #7c3aed;
    color: #ddd6fe;
  }
  60% {
    box-shadow: inset 0 0 3px rgba(16, 185, 129, 0.8), 0 0 5px rgba(16, 185, 129, 0.6);
    text-shadow: 0 0 2px #10b981;
    color: #a7f3d0;
  }
  80% {
    box-shadow: inset 0 0 3px rgba(245, 158, 11, 0.8), 0 0 5px rgba(245, 158, 11, 0.6);
    text-shadow: 0 0 2px #f59e0b;
    color: #fde68a;
  }
}

@keyframes ledBreatheGlow {
  0%, 100% {
    box-shadow: inset 0 0 1px var(--custom-color), 0 0 2px var(--custom-color);
    text-shadow: 0 0 1px var(--custom-color);
    color: #cbd5e1;
    opacity: 0.45;
  }
  50% {
    box-shadow: inset 0 0 4px var(--custom-color), 0 0 6px var(--custom-color);
    text-shadow: 0 0 3px var(--custom-color);
    color: #ffffff;
    opacity: 1;
  }
}

.controls-panel {
  padding: 25px;
}

.control-section {
  margin-bottom: 25px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  padding-bottom: 20px;
}

.control-section:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.section-title {
  font-size: 14px;
  font-weight: 700;
  color: #94a3b8;
  margin: 0 0 15px 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.chassis-color-grid {
  display: flex;
  gap: 15px;
}

.color-btn {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: 2px solid #334155;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}

.color-btn.active {
  border-color: #38bdf8;
  transform: scale(1.1);
  box-shadow: 0 0 15px rgba(56, 189, 248, 0.35);
}

.color-btn:hover:not(.active) {
  transform: scale(1.05);
}

.active-check {
  font-size: 16px;
  font-weight: 900;
  color: white;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
}

.selected-meta {
  font-size: 13px;
  margin-top: 10px;
  color: #64748b;
}

.stickers-shelf {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.sticker-item {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  padding: 10px 5px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  transition: all 0.3s;
}

.sticker-item:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(168, 85, 247, 0.35);
  transform: translateY(-2px);
}

.stk-icon {
  font-size: 26px;
}

.stk-name {
  font-size: 10px;
  font-weight: 600;
  color: #94a3b8;
  text-align: center;
}

.selected-stk-badge {
  background: linear-gradient(135deg, rgba(168, 85, 247, 0.12) 0%, rgba(124, 58, 237, 0.12) 100%);
  border: 1px solid rgba(168, 85, 247, 0.25);
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 12px;
  color: #e9d5ff;
  margin-bottom: 20px;
}

.sliders-grid {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.slider-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.slider-info {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #94a3b8;
}

.range-purple {
  accent-color: #a855f7;
}

.sticker-actions {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}

.btn-stk-action {
  flex: 1;
  padding: 10px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger {
  background-color: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.35);
  color: #fca5a5;
}

.btn-danger:hover {
  background-color: #ef4444;
  color: white;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
}

.btn-outline {
  background-color: transparent;
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #94a3b8;
}

.btn-outline:hover {
  border-color: rgba(255, 255, 255, 0.2);
  color: white;
}

.no-selection {
  background: rgba(255, 255, 255, 0.01);
  border: 1px dashed rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  padding: 20px;
  text-align: center;
  font-size: 13px;
  color: #64748b;
  line-height: 1.5;
}

.led-modes-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

.led-mode-btn {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 12px 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  transition: all 0.3s;
}

.led-mode-btn:hover {
  background: rgba(255, 255, 255, 0.03);
}

.led-mode-btn.active {
  border-color: #2563eb;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, rgba(96, 165, 250, 0.08) 100%);
  box-shadow: 0 0 10px rgba(37, 99, 235, 0.15);
}

.mode-emoji {
  font-size: 20px;
}

.mode-meta {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.mode-name {
  font-size: 11px;
  font-weight: 700;
  color: white;
}

.mode-desc {
  font-size: 8px;
  color: #64748b;
  text-align: center;
  margin-top: 2px;
  line-height: 1.2;
}

.custom-color-picker-wrap {
  background: rgba(15, 23, 42, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 15px;
}

.color-picker-input-group {
  display: flex;
  align-items: center;
  gap: 15px;
}

.picker-box {
  width: 42px;
  height: 42px;
  border-radius: 8px;
  background: none;
  border: 1px solid rgba(255, 255, 255, 0.15);
  outline: none;
  cursor: pointer;
}

.hex-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.hex-info span {
  font-size: 11px;
  color: #64748b;
}

.hex-text-input {
  background: #090d16;
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: white;
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 12px;
  width: 90px;
  font-family: monospace;
  outline: none;
}

.hex-text-input:focus {
  border-color: #2563eb;
}

.quick-colors {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 15px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding-top: 12px;
}

.quick-color-dot {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.15);
  cursor: pointer;
  transition: transform 0.2s;
}

.quick-color-dot:hover {
  transform: scale(1.2);
}

.spec-card-summary {
  background: linear-gradient(135deg, rgba(26, 39, 68, 0.5) 0%, rgba(15, 23, 42, 0.8) 100%);
  border: 1px solid rgba(37, 99, 235, 0.2);
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.spec-card-summary h4 {
  font-size: 13px;
  font-weight: 800;
  color: #60a5fa;
  margin: 0 0 12px 0;
  letter-spacing: 1px;
}

.custom-specs-summary {
  list-style: none;
  padding: 0;
  margin: 0 0 20px 0;
  font-size: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  color: #cbd5e1;
}

.order-custom-btn {
  width: 100%;
  padding: 12px 20px;
  border-radius: 30px;
  border: none;
  color: white;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-rainbow-glow {
  background: linear-gradient(135deg, #1d4ed8 0%, #7c3aed 100%);
  box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
}

.btn-rainbow-glow:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(124, 58, 237, 0.5), 0 0 15px rgba(37, 99, 235, 0.3);
}

.btn-rainbow-glow:active {
  transform: translateY(0);
}





/* =================== TAB 4: GAMIFICATION STYLES =================== */
.coins-wallet-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 25px 35px;
  margin-bottom: 25px;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, rgba(15, 23, 42, 0.45) 100%);
  border: 1px solid rgba(37, 99, 235, 0.2);
}

.wallet-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.wallet-icon {
  font-size: 44px;
  filter: drop-shadow(0 0 8px rgba(37, 99, 235, 0.5));
}

.wallet-label {
  font-size: 13px;
  color: #94a3b8;
  font-weight: 600;
  text-transform: uppercase;
}

.coins-counter {
  font-family: 'Outfit', sans-serif;
  font-size: 26px;
  font-weight: 900;
  margin: 4px 0 0;
}

.text-rainbow {
  background: linear-gradient(135deg, #60a5fa 0%, #c084fc 50%, #f472b6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.wallet-right {
  max-width: 320px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.5;
  text-align: right;
}

.gamified-grid {
  display: grid;
  grid-template-columns: 360px 1fr;
  gap: 25px;
  align-items: stretch;
}

/* Leaderboard */
.leaderboard-panel {
  padding: 25px;
}

.leaderboard-icon {
  font-size: 24px;
}

.leaderboard-sub {
  font-size: 12px;
  color: #64748b;
  margin: 5px 0 20px;
}

.leaderboard-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.leaderboard-row {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  transition: all 0.3s;
}

.leaderboard-row:hover {
  background: rgba(255, 255, 255, 0.04);
}

.leaderboard-row.first-place {
  background: linear-gradient(135deg, rgba(234, 179, 8, 0.06) 0%, rgba(251, 191, 36, 0.02) 100%);
  border-color: rgba(234, 179, 8, 0.25);
}

.rank-badge {
  width: 25px;
  font-size: 13px;
  font-weight: 800;
  display: flex;
  justify-content: center;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  margin: 0 12px;
}

.user-meta {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.user-name {
  font-size: 13px;
  font-weight: 700;
  color: white;
}

.user-title {
  font-size: 10px;
  color: #64748b;
  margin-top: 1px;
}

.user-points {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.referrals-count {
  font-size: 11px;
  font-weight: 700;
  color: #3b82f6;
}

.earned-com {
  font-size: 11px;
  color: #10b981;
  font-weight: 800;
  margin-top: 2px;
}

/* Live payout ticker */
.live-ticker-card {
  margin-top: 25px;
  padding: 15px;
  background: rgba(16, 185, 129, 0.04);
  border: 1px solid rgba(16, 185, 129, 0.15);
  border-radius: 12px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.ticker-dot {
  width: 8px;
  height: 8px;
  background-color: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 8px #10b981;
  margin-top: 4px;
}

.ticker-content-wrapper {
  flex: 1;
}

.ticker-title {
  font-size: 10px;
  font-weight: 800;
  color: #34d399;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.ticker-text {
  margin: 4px 0 0;
  font-size: 11px;
  color: #cbd5e1;
  line-height: 1.4;
}

/* Right Panel Quests */
.quests-rewards-panel {
  display: flex;
  flex-direction: column;
  gap: 25px;
}

.quests-box {
  padding: 25px;
}

.quest-title-icon {
  font-size: 24px;
}

.quests-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-top: 15px;
}

.quest-row-card {
  background: rgba(15, 23, 42, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 12px;
  padding: 15px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
}

.quest-body {
  display: flex;
  align-items: center;
  gap: 15px;
}

.quest-check {
  font-size: 24px;
}

.quest-info h5 {
  font-size: 14px;
  font-weight: 700;
  margin: 0 0 3px 0;
  color: white;
}

.quest-info p {
  margin: 0;
  font-size: 11px;
  color: #64748b;
}

.quest-action-btn {
  background: rgba(37, 99, 235, 0.1);
  border: 1px solid rgba(37, 99, 235, 0.35);
  color: #60a5fa;
  font-size: 11px;
  font-weight: 700;
  padding: 8px 16px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.2s;
}

.quest-action-btn:hover {
  background: #2563eb;
  color: white;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
}

.quest-action-btn.link-btn {
  text-decoration: none;
  display: inline-block;
}

/* Rewards Shop */
.rewards-store {
  padding: 25px;
}

.shop-icon {
  font-size: 24px;
}

.rewards-sub {
  font-size: 12px;
  color: #64748b;
  margin: 5px 0 20px;
}

.rewards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
}

.reward-card-item {
  background: rgba(15, 23, 42, 0.55);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 15px 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  transition: all 0.3s;
}

.reward-card-item:hover {
  transform: translateY(-3px);
  border-color: rgba(37, 99, 235, 0.25);
}

.reward-emoji {
  font-size: 32px;
  margin-bottom: 10px;
}

.reward-card-item h5 {
  font-size: 12px;
  font-weight: 700;
  margin: 0 0 6px 0;
  color: white;
  line-height: 1.4;
  height: 34px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.reward-cost {
  font-size: 10px;
  color: #a78bfa;
  font-weight: 700;
  margin: 0 0 12px 0;
}

.redeem-btn {
  width: 100%;
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
  border: none;
  color: white;
  font-size: 11px;
  font-weight: 700;
  padding: 6px 12px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.2s;
}

.redeem-btn:hover:not(:disabled) {
  transform: scale(1.05);
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
}

.redeem-btn:disabled {
  background: #1e293b;
  color: #475569;
  cursor: not-allowed;
  border: 1px solid rgba(255, 255, 255, 0.02);
}

/* Virtual inventory */
.virtual-inventory {
  margin-top: 25px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding-top: 20px;
}

.virtual-inventory h5 {
  font-size: 13px;
  font-weight: 700;
  color: #cbd5e1;
  margin: 0 0 12px 0;
}

.inventory-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.inventory-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(16, 185, 129, 0.04);
  border: 1px solid rgba(16, 185, 129, 0.15);
  border-radius: 8px;
  padding: 10px 15px;
}

.inv-left {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.inv-name {
  font-size: 12px;
  font-weight: 700;
  color: white;
}

.inv-date {
  font-size: 9px;
  color: #64748b;
}

.inv-code {
  font-family: monospace;
  font-size: 14px;
  font-weight: 800;
  color: #10b981;
  letter-spacing: 0.5px;
}

/* Common animations / effects */
.animate-fade-in {
  animation: fadeIn 0.5s ease-out forwards;
}

.animate-slide-up {
  animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-fade-in-delayed {
  opacity: 0;
  animation: fadeIn 0.5s ease-out 0.2s forwards;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.35s ease, transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(15px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-15px);
}

/* ==========================================
   8. RESPONSIVE MEDIA QUERIES
   ========================================== */
@media (max-width: 1024px) {
  .grid-layout {
    grid-template-columns: 1fr;
  }
  
  .control-panel {
    order: 2;
  }
  
  .chart-panel {
    order: 1;
    min-height: 400px;
  }
  
  .grid-layout-custom {
    grid-template-columns: 1fr;
  }
  
  .sim-grid {
    grid-template-columns: 1fr;
  }
  
  .gamified-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .labs-hero h1 {
    font-size: 32px;
  }
  
  .tabs-container {
    flex-direction: column;
    width: 100%;
    max-width: 320px;
    border-radius: 20px;
  }
  
  .games-grid {
    grid-template-columns: 1fr;
  }
  
  .results-grid {
    grid-template-columns: 1fr;
  }
  
  .stickers-shelf {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .led-modes-grid {
    grid-template-columns: 1fr;
  }
  
  .keys-grid {
    transform: scale(0.85);
    transform-origin: top center;
  }
  
  .laptop-keyboard-container {
    overflow-x: auto;
    padding-bottom: 20px;
  }
  
  .coins-wallet-bar {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
    padding: 20px;
  }
  
  .wallet-right {
    text-align: left;
    max-width: 100%;
  }
  
  .runner-ups-grid {
    grid-template-columns: 1fr;
  }
}

/* ==========================================
   9. INTERACTIVE Exploded 3D Internals View
   ========================================== */
.laptop-internals-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 480px;
  position: relative;
  width: 100%;
  padding: 20px;
  overflow: visible;
}

.scene-3d-wrapper {
  width: 100%;
  max-width: 500px;
  height: 400px;
  perspective: 1200px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: visible;
}

.scene-3d {
  width: 320px;
  height: 220px;
  position: relative;
  transform-style: preserve-3d;
  transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  transform: rotateX(-20deg) rotateY(-35deg);
  will-change: transform;
}

.layer-3d {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  transform-style: preserve-3d;
  transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.5s ease;
  pointer-events: none;
}

.layer-3d img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.55));
}

.layer-3d.dimmed {
  opacity: 0.12;
  filter: blur(2px) grayscale(0.8);
}

.layer-label {
  position: absolute;
  left: -90px;
  top: 50%;
  transform: translateY(-50%) translateZ(20px);
  background: rgba(8, 11, 17, 0.85);
  backdrop-filter: blur(5px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #94a3b8;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  white-space: nowrap;
  opacity: 0;
  transition: opacity 0.4s ease, left 0.4s ease;
  pointer-events: none;
  font-weight: 500;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.scene-3d-wrapper:hover .layer-label {
  opacity: 0.85;
  left: -110px;
}

/* Hotspots positioning */
.hotspot {
  position: absolute;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  cursor: pointer;
  pointer-events: auto;
  z-index: 100;
  transform: translate(-50%, -50%) translateZ(10px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.hotspot-pulse {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: currentColor;
  opacity: 0.8;
  animation: hotspotPulseAnim 1.8s infinite ease-out;
}

.hotspot-label {
  font-size: 9px;
  font-weight: 800;
  color: #fff;
  z-index: 2;
  text-shadow: 0 1px 3px rgba(0,0,0,0.8);
  font-family: monospace;
  letter-spacing: -0.5px;
}

@keyframes hotspotPulseAnim {
  0% { transform: scale(0.6); opacity: 0.9; }
  100% { transform: scale(2.2); opacity: 0; }
}

/* Hotspot coords on motherboard (elite_motherboard dimensions are 320x220) */
.cpu-hotspot {
  top: 48%;
  left: 45%;
  color: #06b6d4;
}

.gpu-hotspot {
  top: 44%;
  left: 65%;
  color: #ec4899;
}

.ram-hotspot {
  top: 25%;
  left: 40%;
  color: #a855f7;
}

.ssd-hotspot {
  top: 75%;
  left: 62%;
  color: #10b981;
}

/* Hotspot coords on cooling-battery (elite_laptop_parts) */
.cooling-hotspot {
  top: 30%;
  left: 55%;
  color: #3b82f6;
}

.battery-hotspot {
  top: 75%;
  left: 48%;
  color: #f59e0b;
}

.display-hotspot {
  top: 50%;
  left: 50%;
  color: #60a5fa;
}

.chassis-hotspot {
  top: 55%;
  left: 50%;
  color: #94a3b8;
}

/* Glowing selection border around hotspots when selected */
.hotspot.active {
  box-shadow: 0 0 0 2px #fff, 0 0 12px 4px currentColor;
}

.manual-rotation-instructions {
  font-size: 12px;
  color: #64748b;
  text-align: center;
  max-width: 440px;
  margin-top: 15px;
  line-height: 1.5;
}

/* Side controls styles */
.internals-controls {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.preset-angles-row {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  flex-wrap: wrap;
}

.btn-preset-scene {
  flex: 1;
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.btn-preset-scene:hover {
  background: rgba(37, 99, 235, 0.15);
  border-color: rgba(37, 99, 235, 0.4);
  color: #fff;
}

/* Parts Inspector */
.parts-quick-select {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  margin-bottom: 15px;
}

.part-select-btn {
  background: rgba(15, 23, 42, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.06);
  color: #94a3b8;
  padding: 8px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 500;
  cursor: pointer;
  text-align: left;
  transition: all 0.3s ease;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.part-select-btn:hover {
  background: rgba(255, 255, 255, 0.04);
  color: #fff;
}

.part-select-btn.active {
  background: linear-gradient(135deg, rgba(6, 182, 212, 0.15) 0%, rgba(124, 58, 237, 0.15) 100%);
  border-color: rgba(6, 182, 212, 0.4);
  color: #fff;
  box-shadow: 0 0 10px rgba(6, 182, 212, 0.15);
}

/* Part details card */
.part-details-card {
  background: rgba(15, 23, 42, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 16px;
}

.part-details-header {
  display: flex;
  gap: 12px;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  padding-bottom: 10px;
  margin-bottom: 10px;
}

.part-emoji {
  font-size: 28px;
}

.part-details-header h5 {
  font-size: 14px;
  font-weight: 700;
  margin: 0;
  color: #fff;
}

.part-sub {
  font-size: 10px;
  color: #64748b;
  display: block;
  margin-top: 2px;
}

.part-description {
  font-size: 11px;
  color: #cbd5e1;
  line-height: 1.5;
  margin-bottom: 12px;
}

.part-specs-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 6px;
  background: rgba(8, 11, 17, 0.4);
  padding: 10px;
  border-radius: 8px;
  margin-bottom: 12px;
}

.part-spec-item {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
}

.spec-lbl {
  color: #64748b;
}

.spec-val {
  font-weight: 600;
}

.part-performance-bar {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.perf-title {
  font-size: 10px;
  color: #94a3b8;
  font-weight: 600;
}

.perf-bar-track {
  width: 100%;
  height: 6px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  overflow: hidden;
}

.perf-bar-fill {
  height: 100%;
  border-radius: 10px;
  width: 0;
  box-shadow: 0 0 10px currentColor;
  transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.perf-score-desc {
  font-size: 10px;
  color: #64748b;
  text-align: right;
}

.no-part-selected {
  background: rgba(15, 23, 42, 0.2);
  border: 1px dashed rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 25px 15px;
  text-align: center;
  color: #64748b;
  font-size: 11px;
  line-height: 1.5;
}

.quest-p {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.5;
  margin-bottom: 12px;
}

.scanning-progress-wrap {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 15px;
}

.scanning-progress-wrap span {
  font-size: 11px;
  font-weight: 600;
  color: #06b6d4;
}

.progress-track-mini {
  width: 100%;
  height: 5px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  overflow: hidden;
}

.progress-fill-mini {
  height: 100%;
  background: linear-gradient(90deg, #06b6d4, #7c3aed);
  border-radius: 10px;
  transition: width 0.4s ease;
}

/* Range sliders cyan styling */
.range-cyan::-webkit-slider-runnable-track {
  background: linear-gradient(90deg, #0f172a, #0891b2);
}
.range-cyan::-webkit-slider-thumb {
  background: #06b6d4;
  box-shadow: 0 0 10px #06b6d4;
}

/* Disabled styling */
.order-custom-btn.disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background: #1e293b !important;
  border-color: #334155 !important;
  box-shadow: none !important;
  color: #64748b !important;
}

.order-custom-btn.disabled::after {
  display: none !important;
}

/* Schematic Overview & Hover Hotspots Styles */
.schematic-active {
  padding: 10px !important;
}

.schematic-view-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-width: 500px;
  position: relative;
  overflow: visible;
}

.schematic-image-wrapper {
  position: relative;
  width: 100%;
  max-width: 440px;
  aspect-ratio: 1;
  background: rgba(8, 12, 22, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: visible;
  box-shadow: inset 0 0 25px rgba(0, 0, 0, 0.7);
  cursor: pointer;
}

.schematic-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 10px 20px rgba(0,0,0,0.5));
  transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), filter 0.5s ease;
}

.schematic-image-wrapper:hover .schematic-img {
  transform: scale(1.03);
  filter: drop-shadow(0 15px 30px rgba(6, 182, 212, 0.35));
}

.schematic-overlay-cta {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(8, 12, 24, 0.9);
  border: 1.5px solid #06b6d4;
  box-shadow: 0 0 25px rgba(6, 182, 212, 0.45);
  padding: 12px 24px;
  border-radius: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.3s ease;
  pointer-events: none;
  opacity: 0.15;
}

.schematic-image-wrapper:hover .schematic-overlay-cta {
  opacity: 1;
  transform: translate(-50%, -50%) scale(1.05);
  background: #06b6d4;
  color: #080c14;
  box-shadow: 0 0 35px rgba(6, 182, 212, 0.8);
}

.schematic-image-wrapper:hover .cta-text {
  color: #080c14;
  font-weight: 800;
}

.cta-text {
  font-size: 11px;
  font-weight: 700;
  color: #06b6d4;
  letter-spacing: 1px;
  white-space: nowrap;
  transition: color 0.3s ease;
}

.cta-pulse-ring {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 30px;
  border: 2px solid #06b6d4;
  opacity: 0.8;
  animation: hotspotPulseAnim 2s infinite ease-out;
}

/* Schematic hotspots pins */
.schematic-pin {
  position: absolute;
  width: 20px;
  height: 20px;
  cursor: pointer;
  z-index: 15;
  transform: translate(-50%, -50%);
  pointer-events: auto;
}

.pin-dot {
  position: absolute;
  width: 8px;
  height: 8px;
  background: #ef4444;
  border-radius: 50%;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 0 8px #ef4444;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.schematic-pin:hover .pin-dot,
.schematic-pin.active .pin-dot {
  background: #06b6d4;
  box-shadow: 0 0 14px 4px #06b6d4;
  transform: translate(-50%, -50%) scale(1.6);
}

.pin-ripple {
  position: absolute;
  width: 28px;
  height: 28px;
  border: 1.5px solid #ef4444;
  border-radius: 50%;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  opacity: 0;
  animation: hotspotPulseAnim 1.6s infinite ease-out;
  transition: all 0.3s ease;
}

.schematic-pin:hover .pin-ripple,
.schematic-pin.active .pin-ripple {
  border-color: #06b6d4;
}

.pin-tooltip {
  position: absolute;
  bottom: 28px;
  left: 50%;
  transform: translateX(-50%) translateY(10px);
  padding: 6px 12px;
  background: rgba(15, 23, 42, 0.95);
  border: 1px solid rgba(6, 182, 212, 0.4);
  box-shadow: 0 10px 25px rgba(0,0,0,0.6), 0 0 15px rgba(6, 182, 212, 0.25);
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  pointer-events: none;
  z-index: 100;
}

.schematic-pin:hover .pin-tooltip,
.schematic-pin.active .pin-tooltip {
  opacity: 1;
  visibility: visible;
  transform: translateX(-50%) translateY(0);
}

.tooltip-emoji {
  font-size: 14px;
}

.tooltip-name {
  font-size: 11px;
  font-weight: 600;
  color: #f8fafc;
}

/* Schematic HUD description details */
.schematic-hud-card {
  width: 100%;
  max-width: 440px;
  margin-top: 15px;
  padding: 15px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  min-height: 110px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  transition: all 0.3s ease;
}

.hud-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}

.hud-emoji {
  font-size: 20px;
}

.hud-header h4 {
  font-size: 14px;
  font-weight: 700;
  color: #06b6d4;
  margin: 0;
  letter-spacing: 0.5px;
}

.hud-desc {
  font-size: 12px;
  color: #94a3b8;
  line-height: 1.6;
  margin: 0;
}

.hud-tip {
  font-size: 10px;
  color: #64748b;
  margin-top: 8px;
  font-style: italic;
}

.hud-pulse-icon {
  font-size: 24px;
  margin-bottom: 6px;
  animation: pulse-slow 2s infinite ease-in-out;
}

.hud-placeholder-text {
  font-size: 11.5px;
  color: #64748b;
  line-height: 1.5;
  text-align: center;
  margin: 0;
}

@keyframes pulse-slow {
  0%, 100% { opacity: 0.5; transform: scale(0.95); }
  50% { opacity: 1; transform: scale(1.05); }
}

.animate-pulse-gentle {
  animation: pulse-slow 3s infinite ease-in-out;
}

.scene-3d-wrapper-outer {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}
</style>
