<template>
  <div class="labs-page">
    <!-- Subtle Valiant Background Elements -->
    <div class="subtle-grid-bg"></div>
    <div class="subtle-glow cyan"></div>
    <div class="subtle-glow purple"></div>

    <!-- Hero / Header Section -->
    <div class="labs-hero">
      <div class="hero-bg-overlay"></div>
      <div class="container hero-content">
        <div class="badge-label animate-fade-in">VinaTech Interactive & Gamification Hub</div>
        <h1 class="animate-slide-up">�?u Tru?ng Tuong T�c & �?i S? C�ng Ngh?</h1>
        <p class="animate-fade-in-delayed">
          Kh�m ph� gi?i h?n ph?n c?ng, thi?t k? laptop c� nh�n v� tham gia h? sinh th�i nhi?m v? nh?n qu� d?c quy?n.
        </p>

        <!-- Tab Switcher (Expanded to 3 tabs) -->
        <div class="tabs-container">
          <button
            @click="activeTab = 'versus'"
            :class="{ active: activeTab === 'versus' }"
            class="tab-btn"
          >
            <span class="tab-icon"></span> �?u Tru?ng Hi?u Nang
          </button>
          <button
            @click="activeTab = 'customizer'"
            :class="{ active: activeTab === 'customizer' }"
            class="tab-btn"
          >
            <span class="tab-icon"></span> C� Nh�n H�a Laptop
          </button>
          <button
            @click="activeTab = 'gamification'"
            :class="{ active: activeTab === 'gamification' }"
            class="tab-btn"
          >
            <span class="tab-icon"></span> �?i S? & Quests
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
                <h3>Chi?n Binh A (Laptop A)</h3>
              </div>

              <div class="form-group">
                <label>Ch?n Laptop d?i d?u:</label>
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
                <h3>�?I �?U CH? S? S?C M?NH</h3>
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
                  <text x="150" y="25" text-anchor="middle" class="axis-label">CPU (X? l�)</text>
                  <text x="260" y="105" text-anchor="start" class="axis-label">GPU (Game/�? h?a)</text>
                  <text x="235" y="235" text-anchor="start" class="axis-label">Pin (Dung lu?ng)</text>
                  <text x="65" y="235" text-anchor="end" class="axis-label">Co d?ng (Kh?i lu?ng)</text>
                  <text x="40" y="105" text-anchor="end" class="axis-label">T?n nhi?t (M�t)</text>
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
                <h3>Chi?n Binh B (Laptop B)</h3>
              </div>

              <div class="form-group">
                <label>Ch?n Laptop d?i d?u:</label>
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
              <span class="sim-icon">??</span>
              <div>
                <h3>�?u Tru?ng Gi? L?p Hi?u Nang Game Th?c T?</h3>
                <p>Ch?n t?a game v� d? ph�n gi?i d? h?a d? m� ph?ng ch? s? FPS & Nhi?t d? ho?t d?ng</p>
              </div>
            </div>

            <div class="sim-grid">
              <!-- Game Selectors -->
              <div class="games-selector">
                <label class="section-sublabel">1. Ch?n t?a Game mu?n th?:</label>
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
                <label class="section-sublabel">2. Thi?t l?p c?u h�nh d? h?a:</label>
                <div class="slider-wrapper">
                  <div class="setting-labels">
                    <span :class="{ active: graphicsSetting === 'low' }">Th?p (1080p)</span>
                    <span :class="{ active: graphicsSetting === 'medium' }">Trung b�nh (2K)</span>
                    <span :class="{ active: graphicsSetting === 'high' }">C?c cao (4K Ultra)</span>
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
                    ? �ang m� ph?ng c?u h�nh: <strong>{{ graphicsSettingLabel }}</strong>
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
                  <div class="stat-compare-title">?? Khung h�nh m?i gi�y (FPS)</div>

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
                  <p class="stat-summary">Mu?t m� t?i thi?u: 60 FPS. M�n gaming l� tu?ng: 144+ FPS.</p>
                </div>

                <!-- Stat Card: TEMPERATURE -->
                <div class="stat-compare-card">
                  <div class="stat-compare-title">??? Nhi?t d? ho?t d?ng (�C)</div>

                  <!-- Laptop A Bar -->
                  <div class="bar-wrapper">
                    <div class="bar-info">
                      <span>{{ laptopA.name }}</span>
                      <span class="value" :class="getTempClass(tempA)">{{ tempA }} �C</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill" :class="getTempBgClass(tempA)" :style="{ width: getPercentage(tempA, 105) + '%' }"></div>
                    </div>
                  </div>

                  <!-- Laptop B Bar -->
                  <div class="bar-wrapper">
                    <div class="bar-info">
                      <span>{{ laptopB.name }}</span>
                      <span class="value" :class="getTempClass(tempB)">{{ tempB }} �C</span>
                    </div>
                    <div class="bar-track">
                      <div class="bar-fill" :class="getTempBgClass(tempB)" :style="{ width: getPercentage(tempB, 105) + '%' }"></div>
                    </div>
                  </div>
                  <p class="stat-summary" v-if="tempA > 85 || tempB > 85">?? C?nh b�o: Nhi?t d? vu?t ngu?ng 85�C c� th? g�y gi?m hi?u nang nh? (thermal throttling).</p>
                  <p class="stat-summary" v-else>? Nhi?t d? ho?t d?ng trong ngu?ng an to�n, h? th?ng t?n nhi?t ho?t d?ng xu?t s?c.</p>
                </div>

                <!-- Stat Card: POWER DRAW -->
                <div class="stat-compare-card">
                  <div class="stat-compare-title">? �i?n nang ti�u th? (TDP Watts)</div>

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
                  <p class="stat-summary">TDP c�ng cao ch?ng t? m�y ti�u hao nang lu?ng nhi?u hon d? bung t?i da hi?u nang.</p>
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
                  ?? V? M�y (Lid Outer)
                </button>
                <button
                  @click="customizerView = 'keyboard'"
                  :class="{ active: customizerView === 'keyboard' }"
                  class="toggle-btn"
                >
                  ?? B�n Ph�m (LED Backlit)
                </button>
                <button
                  @click="customizerView = 'internals'"
                  :class="{ active: customizerView === 'internals' }"
                  class="toggle-btn"
                >
                  ?? M� Ph?ng 3D Linh Ki?n
                </button>
              </div>

              <!-- VIEW 1: OUTSIDE LAPTOP (M?T A + STICKERS) -->
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
                    <span class="logo-text">Predator</span>
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
                <p class="interaction-tip">?? M?o: Nh?n v� k�o nh�n d�n d? di chuy?n t? do tr�n v? m�y!</p>
              </div>

              <!-- VIEW 2: KEYBOARD (M?T C + RGB BACKLIGHT) -->
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
                    <div class="key key-arrow" :style="keyLedStyle">?</div>
                    <div class="key key-arrow" :style="keyLedStyle">?</div>
                  </div>

                  <!-- Large glass trackpad -->
                  <div class="trackpad-glass"></div>
                </div>
                <p class="interaction-tip">?? Ph�a tr�n l� m� ph?ng b�n ph�m t�ch h?p d�n LED RGB sinh d?ng!</p>
              </div>

              <!-- VIEW 3: 3D INTERNALS EXPLODED VIEW / SCHEMATIC OVERVIEW -->
              <div v-else-if="customizerView === 'internals'" class="laptop-internals-container animate-fade-in" :class="{ 'schematic-active': showSchematic }">

                <!-- SCHEMATIC 2D STATE -->
                <div v-if="showSchematic" class="schematic-view-container animate-fade-in">
                  <div class="schematic-image-wrapper" @click="triggerExplosion" title="Click d? k�ch ho?t bung m�y 3D!">
                    <img src="/schematic_laptop.png" alt="So d? laptop ngo�i" class="schematic-img animate-pulse-gentle" />

                    <!-- Pulsing center button to trigger 3D explosion -->
                    <div class="schematic-overlay-cta">
                      <span class="cta-pulse-ring"></span>
                      <span class="cta-text">?? B?M �? BUNG M�Y 3D</span>
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
                    ?? R� chu?t qua c�c di?m d? nh?p nh�y d? xem b? ph?n ngo�i, click v�o ?nh d? BUNG L?P 3D kh�m ph� linh ki?n b�n trong!
                  </div>
                </div>

                <!-- 3D INTERNAL EXPLODED VIEW STATE -->
                <div v-else class="scene-3d-wrapper-outer animate-fade-in">
                  <!-- Mode Selector Toggle between Predator and MacBook Pro -->
                  <div class="model-3d-selector-row">
                    <button
                      @click="selectedModel3D = 'predator'"
                      :class="{ active: selectedModel3D === 'predator' }"
                      class="selector-3d-btn"
                    >
                      ?? Predator Elite (Bung Linh Ki?n)
                    </button>
                    <button
                      @click="selectedModel3D = 'macbook'"
                      :class="{ active: selectedModel3D === 'macbook' }"
                      class="selector-3d-btn"
                    >
                      ?? MacBook Pro (Ngo?i H�nh 3D)
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
                      <!-- ORIGINAL Predator Gaming Laptop Exploded Layers -->
                      <template v-if="selectedModel3D === 'predator'">
                        <!-- Layer 1: Display Panel (Top) -->
                        <div
                          class="layer-3d display-layer"
                          :class="{ dimmed: activePart && activePart !== 'display' }"
                          :style="{
                            transform: `translateZ(${explodedGap * 1.5}px)`
                          }"
                        >
                          <img src="/elite_display_panel.png" alt="M�n h�nh" />

                          <!-- Hotspot for Display Screen -->
                          <div
                            class="hotspot display-hotspot"
                            :class="{ active: activePart === 'display' }"
                            @click="selectPart('display')"
                            @mouseenter="selectPart('display')"
                            title="M�n h�nh Ultra-WQHD 240Hz"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">SCREEN</span>
                          </div>

                          <div class="layer-label">M�n H�nh Ultra-WQHD 240Hz</div>
                        </div>

                        <!-- Layer 2: Keyboard Chassis (Middle Top) -->
                        <div
                          class="layer-3d chassis-layer"
                          :class="{ dimmed: activePart && activePart !== 'chassis' }"
                          :style="{
                            transform: `translateZ(${explodedGap * 0.5}px)`
                          }"
                        >
                          <img src="/elite_chassis_cnc.png" alt="Khung su?n" />

                          <!-- Hotspot for CNC Case -->
                          <div
                            class="hotspot chassis-hotspot"
                            :class="{ active: activePart === 'chassis' }"
                            @click="selectPart('chassis')"
                            @mouseenter="selectPart('chassis')"
                            title="Khung v? CNC Cu?ng L?c"
                          >
                            <span class="hotspot-pulse"></span>
                            <span class="hotspot-label">CASE</span>
                          </div>

                          <div class="layer-label">Khung V? CNC Cu?ng L?c</div>
                        </div>

                        <!-- Layer 3: Motherboard Layer (Center) -->
                        <div
                          class="layer-3d motherboard-layer"
                          :class="{ dimmed: activePart && !['cpu', 'gpu', 'ram', 'ssd'].includes(activePart) }"
                          :style="{
                            transform: `translateZ(0px)`
                          }"
                        >
                          <img src="/elite_motherboard.png" alt="Bo m?ch ch?" />

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

                          <div class="layer-label">Predator Motherboard V2</div>
                        </div>

                        <!-- Layer 4: Internal Cooling & Battery (Bottom) -->
                        <div
                          class="layer-3d cooling-battery-layer"
                          :class="{ dimmed: activePart && !['cooling', 'battery'].includes(activePart) }"
                          :style="{
                            transform: `translateZ(-${explodedGap * 0.8}px)`
                          }"
                        >
                          <img src="/elite_laptop_parts.png" alt="T?n nhi?t v� Pin" />

                          <!-- Hotspots on Bottom Layer with click and hover -->
                          <div
                            class="hotspot cooling-hotspot"
                            :class="{ active: activePart === 'cooling' }"
                            @click="selectPart('cooling')"
                            @mouseenter="selectPart('cooling')"
                            title="H? th?ng t?n nhi?t Dual-Turbo"
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

                          <div class="layer-label">T?n Nhi?t Dual-Turbo & Pin VinaVolt</div>
                        </div>
                      </template>

                      <!-- PREMIUM CSS 3D MacBook Pro Model -->
                      <div
                        v-else-if="selectedModel3D === 'macbook'"
                        class="macbook-3d"
                        :class="[macbookColor, { 'dimmed-others': activeMacbookPart }]"
                      >
                        <!-- 1. MacBook Base (Th�n m�y) -->
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

                        <!-- 2. MacBook Screen Hinge (N?p g?p m�n h�nh xoay b?n l?) -->
                        <div
                          class="macbook-screen-hinge"
                          :style="{ transform: `rotateX(-${macbookHingeAngle}deg)` }"
                          :class="{ highlighted: activeMacbookPart && ['screen', 'logo'].includes(activeMacbookPart) }"
                        >
                          <!-- Face 1: Screen Inner (M?t trong m�n h�nh hi?n th? macOS) -->
                          <div class="macbook-screen-inner">
                            <div class="macbook-bezel">
                              <div class="macbook-notch-area">
                                <span class="mb-webcam"></span>
                              </div>

                              <div class="macbook-display-panel" :class="[macbookWallpaper]">
                                <!-- macOS Menu Bar -->
                                <div class="macos-menubar">
                                  <span class="macos-apple-icon">?</span>
                                  <span class="menu-bold">Finder</span>
                                  <span>File</span>
                                  <span>Edit</span>
                                  <span>View</span>
                                  <span>Go</span>
                                  <span>Window</span>
                                  <span class="menubar-right-status">?? 100%  ??  22:13</span>
                                </div>

                                <!-- Desktop Center App mock -->
                                <div class="macos-desktop-center">
                                  <h4 class="macos-title-logo">MacBook Pro 16"</h4>
                                  <p class="macos-sub">Apple M3 Max Chip</p>
                                </div>

                                <!-- macOS Dock Bar -->
                                <div class="macos-dock-bar">
                                  <span class="dock-icon finder">??</span>
                                  <span class="dock-icon safari">??</span>
                                  <span class="dock-icon terminal">??</span>
                                  <span class="dock-icon store">??</span>
                                  <span class="dock-icon settings">??</span>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Face 2: Screen Outer (M?t ngo�i n?p m�y ch?a Apple Logo ph�t s�ng) -->
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
                    ?? R� chu?t qua c�c di?m nh?p nh�y d? qu�t chi ti?t, di chu?t t? do d? xoay kh�ng gian 3D to�n c?nh!
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Controls Dashboard -->
            <div class="controls-panel card-glass">
              <div class="panel-header">
                <h3>BẰNG �I?U KHI?N S�NG T?O</h3>
              </div>

              <!-- CONTROLS FOR VIEW 1: COVER COLOR & STICKERS -->
              <div v-if="customizerView === 'lid'" class="lid-controls">

                <!-- 1. Color Selection -->
                <div class="control-section">
                  <h4 class="section-title">1. Ch?n m�u kim lo?i n?p m�y:</h4>
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
                      <span class="active-check" v-if="chassisColor === color.id">?</span>
                    </button>
                  </div>
                  <p class="selected-meta">V? m�u hi?n t?i: <strong>{{ selectedChassisName }}</strong></p>
                </div>

                <!-- 2. Stickers Shelf -->
                <div class="control-section">
                  <h4 class="section-title">2. Thu vi?n Nh�n D�n (Stickers):</h4>
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
                  <h4 class="section-title text-rainbow">3. Ch?nh s?a Sticker dang ch?n:</h4>
                  <div class="selected-stk-badge">
                    <span>Nh�n dang ch?nh: <strong>{{ selectedSticker.icon }} {{ selectedSticker.name }}</strong></span>
                  </div>

                  <div class="sliders-grid">
                    <div class="slider-group">
                      <div class="slider-info">
                        <span>Xoay g�c (Rotate)</span>
                        <span>{{ selectedSticker.rotate }}�</span>
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
                        <span>K�ch thu?c (Scale)</span>
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
                      ??? X�a Nh�n D�n n�y
                    </button>
                    <button @click="clearAllStickers" class="btn-stk-action btn-outline">
                      ?? X�a t?t c? Sticker
                    </button>
                  </div>
                </div>
                <div class="control-section no-selection" v-else>
                  <p>?? Vui l�ng click ch?n 1 Sticker tr�n n?p m�y d? xoay, thu ph�ng ho?c x�a.</p>
                </div>

              </div>

              <!-- CONTROLS FOR VIEW 2: RGB LED LIGHTING -->
              <div v-else-if="customizerView === 'keyboard'" class="keyboard-controls">

                <!-- 1. Select Led Backlit Mode -->
                <div class="control-section">
                  <h4 class="section-title">1. Ch?n Hi?u ?ng ��n n?n:</h4>
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
                  <h4 class="section-title">2. Ch?n m�u LED Neon t�y ch?nh:</h4>
                  <div class="custom-color-picker-wrap">
                    <div class="color-picker-input-group">
                      <input
                        type="color"
                        v-model="customRgbColor"
                        class="picker-box"
                      />
                      <div class="hex-info">
                        <span>M� m�u Hex:</span>
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
                  <h4>C?U H�NH TR?I NGHI?M �� CH?N</h4>
                  <ul class="custom-specs-summary">
                    <li>?? M�u v? m�y: <strong>{{ selectedChassisName }}</strong></li>
                    <li>?? Nh�n trang tr�: <strong>{{ appliedStickers.length }} Stickers d� d�n</strong></li>
                    <li>?? LED B�n ph�m: <strong>{{ selectedLedModeLabel }}</strong></li>
                  </ul>
                  <button @click="handleSaveDesign" class="order-custom-btn btn-rainbow-glow">
                    ?? Luu Thi?t K? & Nh?n 15 VinaCoins
                  </button>
                </div>

              </div>

              <!-- CONTROLS FOR VIEW 3: 3D INTERNALS EXPLODED VIEW -->
              <div v-else-if="customizerView === 'internals'" class="internals-controls">

                <!-- SCHEMATIC OVERVIEW CONTROLS -->
                <div v-if="showSchematic" class="schematic-controls animate-fade-in">
                  <!-- Section 1: Guide / Start CTA -->
                  <div class="control-section">
                    <h4 class="section-title">1. So �? Thi?t K? Ngo�i:</h4>
                    <p class="quest-p">��y l� b?n v? m� ph?ng c�c th�nh ph?n b�n ngo�i c?a Laptop. Di chu?t qua c�c di?m nh?p nh�y tr�n m� h�nh d? ch?n do�n th�ng s?.</p>

                    <button @click="triggerExplosion" class="order-custom-btn btn-rainbow-glow w-full mt-4" style="margin-top: 15px; width: 100%;">
                      ?? K�CH HO?T BUNG M�Y 3D B�N TRONG
                    </button>
                  </div>

                  <!-- Section 2: HUD inspector details -->
                  <div class="control-section parts-inspector">
                    <h4 class="section-title text-rainbow">2. B? Qu�t B? Ph?n Ngo�i:</h4>

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
                        ?? Click d? BUNG M�Y xem linh ki?n b�n trong!
                      </div>
                    </div>
                    <div v-else class="no-part-selected">
                      <p>?? R� chu?t ho?c b?m ch?n linh ki?n ngo�i d? thanh tra chi ti?t th�ng sự cốu t?o.</p>
                    </div>
                  </div>
                </div>

                <!-- 3D EXPLODED SETTINGS CONTROLS -->
                <div v-else class="internals-3d-controls animate-fade-in">
                  <!-- Button to go back to schematic -->
                  <div class="preset-angles-row mb-4" style="margin-bottom: 15px;">
                    <button @click="showSchematic = true" class="btn-preset-scene w-full" style="border-color: #06b6d4; background: rgba(6, 182, 212, 0.1); width: 100%;">
                      ?? Xem So �? Thi?t K? Ngo�i
                    </button>
                  </div>

                  <!-- 3D Scene Adjusters -->
                  <div class="control-section">
                    <h4 class="section-title">1. G�c Xoay & �? Bung Linh Ki?n:</h4>

                    <div class="sliders-grid">
                      <div class="slider-group">
                        <div class="slider-info">
                          <span>�? bung linh ki?n (Exploded View)</span>
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
                          <span>{{ explodedRotateY }}�</span>
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
                          <span>Xoay D?c (Rotate X)</span>
                          <span>{{ explodedRotateX }}�</span>
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
                      <button @click="reset3DScene" class="btn-preset-scene">?? �?t L?i Scene</button>
                      <button @click="presetScene('exploded')" class="btn-preset-scene">?? Bung L?p 3D</button>
                      <button @click="presetScene('assembled')" class="btn-preset-scene">?? L?p R�p M�y</button>
                    </div>
                  </div>

                  <!-- Component Details Inspector -->
                  <div class="control-section parts-inspector">
                    <h4 class="section-title text-rainbow">2. Thanh Tra Linh Ki?n:</h4>

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
                        <div class="perf-title">? Ch? s? hi?u nang (Power Score)</div>
                        <div class="perf-bar-track">
                          <div class="perf-bar-fill animate-width" :style="{ width: selectedPartData.score + '%', background: selectedPartData.color }"></div>
                        </div>
                        <div class="perf-score-desc">C?p d?: <strong>{{ selectedPartData.score }} / 100</strong></div>
                      </div>
                    </div>
                    <div v-else class="no-part-selected">
                      <p>?? R� chu?t ho?c click ch?n linh ki?n tr�n h�nh ?nh 3D d? qu�t chi ti?t th�ng s? k? thu?t.</p>
                    </div>
                  </div>

                  <!-- Custom Coin boost integration -->
                  <div class="control-section spec-card-summary">
                    <h4>NHI?M V? QU�T LINH KI?N</h4>
                    <p class="quest-p">Qu�t d?y d? {{ componentParts.length }} linh ki?n ch�nh c?a laptop d? hi?u r� co ch? v?n h�nh v� t�ch lu? th�m ph?n thu?ng!</p>
                    <div class="scanning-progress-wrap">
                      <span>Ti?n tr�nh qu�t: {{ scannedPartsCount }} / {{ componentParts.length }} linh ki?n</span>
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
                      {{ hasClaimedScanReward ? '?? �� nh?n 25 VinaCoins' : (scannedPartsCount < componentParts.length ? '?? H�y qu�t d? ' + componentParts.length + ' linh ki?n' : '?? Nh?n ngay 25 VinaCoins!') }}
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
              <span class="wallet-icon">??</span>
              <div>
                <span class="wallet-label">VinaCoins Wallet của bạn:</span>
                <h2 class="coins-counter text-rainbow">{{ vinaCoins }} VinaCoins</h2>
              </div>
            </div>
            <div class="wallet-right">
              <p>Ho�n th�nh c�c nhi?m v? h�ng ng�y d? t�ch lu? xu d?i Voucher gi?m gi� Laptop th?c t?!</p>
            </div>
          </div>

          <div class="gamified-grid">

            <!-- Left panel: Ambassador Leaderboard -->
            <div class="leaderboard-panel card-glass">
              <div class="panel-header">
                <span class="leaderboard-icon">??</span>
                <h3>�?I S? DANH V?NG TH�NG 5</h3>
              </div>
              <p class="leaderboard-sub">B?ng x?p h?ng �?i S? li�n k?t gi?i thi?u (Affiliate) xu?t s?c nh?t h? th?ng.</p>

              <!-- Top 5 List -->
              <div class="leaderboard-list">
                <div
                  v-for="(user, idx) in leaderboardUsers"
                  :key="'user-'+idx"
                  class="leaderboard-row"
                  :class="{ 'first-place': idx === 0 }"
                >
                  <div class="rank-badge" :class="'rank-'+(idx+1)">
                    <span v-if="idx === 0">??</span>
                    <span v-else-if="idx === 1">??</span>
                    <span v-else-if="idx === 2">??</span>
                    <span v-else>{{ idx + 1 }}</span>
                  </div>
                  <img :src="user.avatar" :alt="user.name" class="user-avatar" />
                  <div class="user-meta">
                    <span class="user-name">{{ user.name }}</span>
                    <span class="user-title">{{ user.title }}</span>
                  </div>
                  <div class="user-points">
                    <span class="referrals-count">??? {{ user.refs }} Lu?t</span>
                    <span class="earned-com">{{ formatPrice(user.com) }}</span>
                  </div>
                </div>
              </div>

              <!-- Live payout ticker -->
              <div class="live-ticker-card">
                <div class="ticker-dot animate-pulse"></div>
                <div class="ticker-content-wrapper">
                  <span class="ticker-title">Thanh to�n hoa h?ng tr?c tuy?n:</span>
                  <p class="ticker-text">{{ recentPayoutText }}</p>
                </div>
              </div>
            </div>

            <!-- Right panel: Quests and Rewards Shop -->
            <div class="quests-rewards-panel">

              <!-- 1. Daily Quests Shelf -->
              <div class="quests-box card-glass">
                <div class="panel-header">
                  <span class="quest-title-icon">?</span>
                  <h3>Nhi?m V? H�ng Ng�y</h3>
                </div>

                <div class="quests-list">
                  <!-- Quest 1 -->
                  <div class="quest-row-card">
                    <div class="quest-body">
                      <span class="quest-check">??</span>
                      <div class="quest-info">
                        <h5>Chia s? du?ng d?n Ti?p th? li�n k?t</h5>
                        <p>Sao ch�p link ref v� chia s? l�n MXH d? nh?n xu</p>
                      </div>
                    </div>
                    <button @click="completeShareQuest" class="quest-action-btn">
                      Copy Link (+20 ??)
                    </button>
                  </div>

                  <!-- Quest 2 -->
                  <div class="quest-row-card">
                    <div class="quest-body">
                      <span class="quest-check">??</span>
                      <div class="quest-info">
                        <h5>�?c b?n tin C�ng ngh? Predator m?i</h5>
                        <p>Gh� tham m?c tin t?c d? t�m hi?u th�m xu hu?ng c�ng ngh?</p>
                      </div>
                    </div>
                    <router-link to="/news" @click="completeNewsQuest" class="quest-action-btn link-btn">
                      Gh� xem (+10 ??)
                    </router-link>
                  </div>

                  <!-- Quest 3 -->
                  <div class="quest-row-card">
                    <div class="quest-body">
                      <span class="quest-check">???</span>
                      <div class="quest-info">
                        <h5>M?i m?t ngu?i b?n gh� tham VinaTech</h5>
                        <p>Nh?p t�n b?n b� d? gi?i thi?u mua laptop l�n d?i</p>
                      </div>
                    </div>
                    <button @click="completeReferralQuest" class="quest-action-btn">
                      Gi?i thi?u (+50 ??)
                    </button>
                  </div>
                </div>
              </div>

              <!-- 2. Rewards Store Shop -->
              <div class="rewards-store card-glass">
                <div class="panel-header">
                  <span class="shop-icon">??</span>
                  <h3>VinaCoins Rewards Shop</h3>
                </div>
                <p class="rewards-sub">�?i xu nh?n Voucher tr? gi� d?c quy?n mua laptop!</p>

                <div class="rewards-grid">
                  <div
                    v-for="reward in rewardsShop"
                    :key="reward.id"
                    class="reward-card-item"
                  >
                    <span class="reward-emoji">{{ reward.emoji }}</span>
                    <h5>{{ reward.name }}</h5>
                    <p class="reward-cost">?? Gi�: {{ reward.cost }} VinaCoins</p>
                    <button
                      @click="redeemReward(reward)"
                      :disabled="vinaCoins < reward.cost"
                      class="redeem-btn"
                    >
                      �?i ngay
                    </button>
                  </div>
                </div>

                <!-- Virtual Wallet Inventory for redeemed codes -->
                <div class="virtual-inventory" v-if="myRewards.length">
                  <h5>?? V� Voucher �� �?i C?a B?n:</h5>
                  <div class="inventory-list">
                    <div v-for="item in myRewards" :key="item.id" class="inventory-row">
                      <div class="inv-left">
                        <span class="inv-name">{{ item.name }}</span>
                        <span class="inv-date">�?i ng�y: {{ item.date }}</span>
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
import { productImageUrl } from '@/services/urls';
import Swal from 'sweetalert2';

const formatPrice = (p) => new Intl.NumberFormat('vi-VN').format(p) + 'd';

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
    name: 'Predator Elite Beast Pro',
    fullName: 'Predator Elite Beast Pro (RTX 4090 / Intel i9 / 64GB)',
    price: 89990000,
    cpu: 'Intel Core i9-14900HX (24 cores, 5.8GHz)',
    gpu: 'NVIDIA GeForce RTX 4090 16GB GDDR6',
    ram: '64GB DDR5 5600MHz Dual Channel',
    img: '/elite_motherboard.png',
    metrics: { cpu: 98, gpu: 99, battery: 55, portability: 45, cooling: 98 }
  },
  {
    id: 'preset-2',
    name: 'Predator Elite Air M3',
    fullName: 'Predator Elite Air (Apple M3 Max / 32GB / 1TB)',
    price: 64990000,
    cpu: 'Apple M3 Max (16-core CPU, 40-core GPU)',
    gpu: 'Apple M3 Max 40-Core GPU',
    ram: '32GB Unified Memory LPDDR5X',
    img: '/hero_3d_laptop.png',
    metrics: { cpu: 95, gpu: 85, battery: 98, portability: 95, cooling: 88 }
  },
  {
    id: 'preset-3',
    name: 'Predator Elite Scholar Plus',
    fullName: 'Predator Elite Scholar Plus (RTX 4060 / Ryzen 7 / 16GB)',
    price: 29990000,
    cpu: 'AMD Ryzen 7 8845HS (8 cores, 5.1GHz)',
    gpu: 'NVIDIA GeForce RTX 4060 8GB GDDR6',
    ram: '16GB LPDDR5X 7467MT/s',
    img: '/elite_workspace.png',
    metrics: { cpu: 85, gpu: 78, battery: 80, portability: 85, cooling: 82 }
  },
  {
    id: 'preset-4',
    name: 'Predator Scholar Eco',
    fullName: 'Predator Scholar Eco (Intel i5 / 16GB / 512GB)',
    price: 18490000,
    cpu: 'Intel Core i5-13420H (8 cores, 4.6GHz)',
    gpu: 'Intel Iris Xe Graphics',
    ram: '16GB LPDDR5 4800MHz',
    img: '/elite_shipping.png',
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
  if (realProducts.value.length === 0) {
    isLoadingProducts.value = true;
  }
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
          const gObj = ts.find(t => t.ten === 'GPU' || t.ten === 'gpu' || t.ten === 'Card d? h?a');
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
        img: productImageUrl(p, firstBt, '/hero_3d_laptop.png'),
        metrics: { cpu: cpuScore, gpu: gpuScore, battery: batteryScore, portability: portScore, cooling: coolScore }
      };
    });

    // Save to cache for instant load next time
    try {
      localStorage.setItem('predator_labs_real_products', JSON.stringify(realProducts.value));
    } catch(e) {}
  } catch (e) {
    console.error('L?i khi fetch s?n ph?m th?c t?:', e);
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
  { id: 'wukong', name: 'Black Myth: Wukong', genre: 'Action RPG / �? h?a 3D c?c n?ng', emoji: '??', base: 52 },
  { id: 'cyberpunk', name: 'Cyberpunk 2077', genre: 'Sci-fi Open World / Ray-Tracing', emoji: '??', base: 56 },
  { id: 'cs2', name: 'Counter-Strike 2', genre: 'FPS Esport / T?c d? ph?n h?i', emoji: '??', base: 190 },
  { id: 'lol', name: 'League of Legends', genre: 'MOBA nh? / ?n d?nh t?i da', emoji: '??', base: 310 }
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
  if (graphicsSetting.value === 'low') return 'Ch?t lu?ng Th?p (1080p - Hi?u nang cao)';
  if (graphicsSetting.value === 'high') return 'C?c cao Ultra (4K UHD - �? h?a d?nh cao)';
  return 'Trung b�nh (1440p QHD - Khuy�n d�ng)';
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
const selectedModel3D = ref('predator'); // 'predator' or 'macbook'
const macbookHingeAngle = ref(110); // 0 to 135
const macbookColor = ref('spacegray'); // 'spacegray' or 'silver'
const macbookWallpaper = ref('sequoia'); // 'sequoia', 'terminal', 'store'
const activeMacbookPart = ref(null);

const macbookParts = [
  {
    id: 'screen',
    name: 'M�n h�nh Liquid Retina XDR 16.2"',
    emoji: '???',
    sub: '�? ph�n gi?i 3.4K, 1600 nits, ProMotion 120Hz',
    desc: 'T?m n?n Mini-LED Extreme Dynamic Range d?nh cao, t? l? tuong ph?n 1.000.000:1, d?i m�u r?ng P3 chu?n studio, t?n s? qu�t th�ch ?ng 120Hz mu?t m� kinh ng?c.',
    specs: {
      '�? ph�n gi?i': '3456 x 2234 (3.4K)',
      '�? s�ng t?i da': '1600 nits Peak HDR',
      'C�ng ngh? n?n': 'Mini-LED 10,000 b�ng LED',
      'T?n s? qu�t': 'ProMotion 120Hz th�ch ?ng'
    },
    score: 98,
    color: '#06b6d4'
  },
  {
    id: 'keyboard',
    name: 'B�n ph�m Magic Keyboard & Touch ID',
    emoji: '??',
    sub: 'H�nh tr�nh ph�m t?i uu, b?o m?t sinh tr?c h?c',
    desc: 'Ph�m g� �m �i, ?n d?nh tuy?t d?i v?i co ch? c?t k�o. H�ng ph�m ch?c nang full-size ti?n l?i c�ng c?m bi?n v�n tay Touch ID si�u b?o m?t t�ch h?p ? g�c ph?i.',
    specs: {
      'Lo?i co c?u': 'Co c?u c?t k�o (Scissor-switch)',
      '��n n?n': 'LED don tr?ng th�ng minh',
      'B?o m?t': 'Touch ID Secure Enclave'
    },
    score: 92,
    color: '#3b82f6'
  },
  {
    id: 'trackpad',
    name: 'B�n r� Force Touch Trackpad',
    emoji: '???',
    sub: 'C?m ?ng l?c th�ng minh, ph?n h?i haptic',
    desc: 'B�n r� c?m ?ng l?c l?n nh?t th? gi?i, h? tr? nh?n di?n nhi?u c?p d? l?c nh?n v� v� v�n c? ch? Multi-Touch th�ng minh. Kh�ng c� chi ti?t chuy?n d?ng v?t l�, gi? l?p l?c nh?n b?ng b? rung haptic c?c k? ch�nh x�c.',
    specs: {
      'Co ch? ho?t d?ng': 'C?m bi?n l?c + Taptic Engine',
      'Ch?t li?u b? m?t': 'K�nh m? Acid-etched Glass cao c?p',
      'H? tr? c? ch?': 'Multi-Touch & Force Click'
    },
    score: 95,
    color: '#a855f7'
  },
  {
    id: 'logo',
    name: 'Logo T�o khuy?t m? guong ph�t s�ng',
    emoji: '??',
    sub: 'Bi?u tu?ng d?ng c?p thi?t k? Apple',
    desc: 'Bi?u trung Apple được gia c�ng c?t laser ch�nh x�c b?ng ch?t li?u k�nh tr�ng guong b�ng b?y ch?ng xu?c, t?o di?m nh?n th?m m? thanh l?ch d?c trung tr�n n?p nh�m.',
    specs: {
      'Ch?t li?u': 'K�nh cu?ng l?c Mirror-polished',
      '�?c di?m': 'Gia c�ng ch�nh x�c, ch?ng b�m v�n tay',
      'Hi?u ?ng': 'Ph�t s�ng neon d?i m�u khi hover'
    },
    score: 90,
    color: '#f59e0b'
  },
  {
    id: 'speakers',
    name: 'H? th?ng 6 loa ngo�i Spatial Audio',
    emoji: '??',
    sub: 'Loa tr?m kh? l?c force-cancelling, �m thanh v�m',
    desc: 'H? th?ng �m thanh t?t nh?t tr�n m?i chi?c laptop th? gi?i v?i 4 loa tr?m kh? l?c tri?t ti�u rung d?ng v� 2 loa b?ng hi?u nang cao. H? tr? Spatial Audio khi ph�t nh?c ho?c phim chu?n Dolby Atmos.',
    specs: {
      'C?u h�nh loa': '6 Loa Hi-Fi (4 Woofers + 2 Tweeters)',
      'C�ng ngh?': 'Spatial Audio & Dolby Atmos v�m',
      'H? tr? mic': 'C?m 3 micro d?nh hu?ng chu?n studio'
    },
    score: 97,
    color: '#ec4899'
  },
  {
    id: 'ports',
    name: 'C?ng MagSafe 3 & Thunderbolt 4',
    emoji: '??',
    sub: 'K?t n?i da nang, s?c h�t nam ch�m an to�n',
    desc: 'Trang b? c?ng s?c nam ch�m MagSafe 3 c?c k? an to�n, t? d?ng bung ra khi v?p c�p s?c. �i k�m 3 c?ng Thunderbolt 4 (USB-C) bang th�ng si�u t?c 40Gbps, khe d?c th? nh? SDXC v� c?ng HDMI 2.1 xu?t m�n h�nh 8K.',
    specs: {
      'MagSafe 3': 'H�t nam ch�m s?c nhanh 140W',
      'Thunderbolt 4': '3 C?ng USB-C t?c d? 40Gbps',
      '�?u ra h�nh ?nh': 'HDMI 2.1 & �?u d?c th? nh? SDXC'
    },
    score: 94,
    color: '#10b981'
  },
  {
    id: 'unibody',
    name: 'V? nh�m nguy�n kh?i Aluminium Unibody',
    emoji: '???',
    sub: 'Nh�m t�i ch? 100% b?o v? m�i tru?ng, si�u b?n',
    desc: 'Khung su?n MacBook Pro được d�c v� ti?n t? m?t kh?i nh�m duy nh?t si�u ch?u l?c. V?t li?u l�m b?ng nh�m t�i ch? 100% được gia c�ng vi-anode ch?ng b�m m? h�i v� xu?c dam hi?u qu?.',
    specs: {
      'Ch?t li?u': '100% Nh�m Series 6000 t�i ch?',
      'M�u s?c': 'Space Gray (X�m) / Silver (B?c)',
      'Tr?ng lu?ng': 'Ch? 2.1 kg cho phi�n b?n 16.2"'
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
    name: 'Camera AI FHD & C?m bi?n IR h?ng ngo?i',
    emoji: '??',
    x: 48, y: 7,
    desc: 'H? th?ng camera d? ph�n gi?i cao k?t h?p c?m bi?n h?ng ngo?i h? tr? b?o m?t Windows Hello m? kh�a b?ng khu�n m?t 3D, t? d?ng can ch?nh khung h�nh AI.'
  },
  {
    id: 'screen',
    name: 'M�n H�nh Ultra-OLED 16" 2.5K 240Hz',
    emoji: '???',
    x: 60, y: 25,
    desc: 'T?m n?n OLED cao c?p th? h? m?i, d? ph? m�u di?n ?nh 100% DCI-P3, t?n s? qu�t 240Hz si�u mu?t v� d? s�ng c?c d?i 600 nits cho tr?i nghi?m h�nh ?nh tuy?t m?.'
  },
  {
    id: 'top_panel',
    name: 'Khung V? N?p Tr�n H?p Kim Nh�m',
    emoji: '??',
    x: 35, y: 22,
    desc: 'N?p v? nh�m d�ng 6000 si�u nh? gia c�ng CNC nguy�n kh?i tinh x?o, x? l� anode m?n m�ng ch?ng xu?c v� tang d? ch?u l?c t�c d?ng v?t l� b�n ngo�i.'
  },
  {
    id: 'keyboard',
    name: 'B�n Ph�m Co LED Backlit Co H?c',
    emoji: '??',
    x: 55, y: 55,
    desc: 'Lu?i ph�m b?m co h?c th? h? m?i v?i h�nh tr�nh ph�m 1.5mm ph?n h?i x�c gi�c t?i uu, trang b? d�n n?n LED RGB d?c l?p l?p l�nh.'
  },
  {
    id: 'touchpad',
    name: 'B�n R� Haptic Glass Touchpad',
    emoji: '???',
    x: 63, y: 62,
    desc: 'Di?n t�ch c?c r?ng, ph? k�nh cu?ng l?c mu?t m�, t�ch h?p d?ng co rung ph?n h?i l?c Haptic ch�nh x�c thay cho co ch? ph�m b?m v?t l� truy?n th?ng.'
  },
  {
    id: 'power_btn',
    name: 'N�t Ngu?n V�n Tay 1 Ch?m',
    emoji: '??',
    x: 31, y: 58,
    desc: '�u?c trang b? c?m bi?n sinh tr?c h?c v�n tay si�u nh?y du?i n�t ngu?n gi�p dang nh?p Windows an to�n ch? trong m?t l?n ch?m.'
  },
  {
    id: 'charging_port',
    name: 'C?ng S?c DC Si�u T?c VinaCharge',
    emoji: '??',
    x: 27, y: 56,
    desc: 'C?ng s?c c?p ngu?n c�ng su?t cao chuy�n d?ng, h? tr? c�ng ngh? s?c si�u nhanh gi�p h?i sinh 80% dung lu?ng pin trong v�ng 45 ph�t s?c.'
  },
  {
    id: 'usb_ports',
    name: 'C?ng USB Si�u T?c & Thunderbolt 4',
    emoji: '?',
    x: 34, y: 64,
    desc: 'C?ng giao ti?p da nang bang th�ng si�u r?ng 40Gbps h? tr? truy?n dữ liệu, s?c nhanh Power Delivery v� xu?t ra th�m 2 m�n h�nh r?i 4K.'
  },
  {
    id: 'base_panel',
    name: 'V? ��y H?p Kim Magie Si�u B?n',
    emoji: '???',
    x: 77, y: 56,
    desc: '�? m�y d�c nguy�n kh?i Magie si�u nh?, thi?t k? khe lu?i t?n nhi?t h�nh t? ong tang luu lu?ng kh� h�t v�o qu?t t?n nhi?t th�m 30%.'
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

  // Xoay ngang 180 d? (t? -180 d?n 180 d? to�n c?nh)
  explodedRotateY.value = Math.round(relX * 360);

  // Xoay d?c (t? -45 d?n 45 d?)
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
    name: 'M�n h�nh Ultra-WQHD 240Hz',
    emoji: '???',
    sub: 'T?m N?n OLED �i?n ?nh Th? H? M?i',
    desc: 'T?m n?n OLED cao c?p d? ph? m�u 100% DCI-P3 si�u trung th?c, h? tr? HDR600, t?n s? qu�t 240Hz l� tu?ng cho game th? FPS v� nh� thi?t k? chuy�n nghi?p.',
    specs: {
      'K�ch thu?c': '16 inches',
      '�? Ph�n Gi?i': '2.5K WQHD (2560x1600)',
      'T?n S? Qu�t': '240Hz si�u mu?t',
      'C�ng Ngh?': 'OLED HDR600'
    },
    score: 96,
    color: '#60a5fa'
  },
  {
    id: 'chassis',
    name: 'Khung V? Kim Lo?i CNC',
    emoji: '??',
    sub: 'V? Nh�m D�ng 6000 Nguy�n Kh?i Cu?ng L?c',
    desc: 'Khung m�y h?p kim nh�m-magie d�ng 6000 ti?n CNC si�u t? m?, tang cu?ng c?u tr�c t?n nhi?t th? d?ng, ch?u l?c v� ch?ng va d?p ti�u chu?n qu�n d?i.',
    specs: {
      'Ch?t Li?u': 'H?p Kim Nh�m-Magie 6000',
      '�? D�y': 'Ch? 15.9 mm',
      'Tr?ng Lu?ng v?': 'Gia c? ch?u l?c 25kg',
      'Quy Tr�nh': 'C?t CNC nguy�n kh?i + Anode ph? m?n'
    },
    score: 93,
    color: '#94a3b8'
  },
  {
    id: 'cpu',
    name: 'CPU Intel Core i9-14900HX',
    emoji: '??',
    sub: 'Vi X? L� Si�u C?p H?ng Raptor Lake',
    desc: 'Trang b? 24 nh�n (8 nh�n P-core hi?u nang cao & 16 nh�n E-core ti?t ki?m di?n), xung nh?p turbo t?i da l�n t?i 5.8 GHz. H? tr? Intel Thread Director t?i uu lu?ng choi game v� d?ng h�nh 3D d?nh cao.',
    specs: {
      'S? Nh�n / Lu?ng': '24 Cores / 32 Threads',
      'Xung Nh?p': 'Xung turbo 5.8 GHz',
      'B? Nh? �?m': '36MB Smart Cache',
      'C�ng Su?t ti�u th?': '55W - 157W Turbo'
    },
    score: 98,
    color: '#06b6d4'
  },
  {
    id: 'gpu',
    name: 'GPU NVIDIA RTX 4090 Laptop',
    emoji: '??',
    sub: 'Qu�i Th� �? H?a C?n C?c Ada Lovelace',
    desc: 'S? h?u 16GB VRAM GDDR6 si�u t?c, c�ng ngh? DLSS 3.0 v?i Frame Generation t�i t?o khung h�nh AI mu?t m� g?p 4 l?n. H? tr? Full Ray-Tracing mang l?i th? gi?i ?o lung linh ch�n th?c.',
    specs: {
      'Nh�n CUDA': '9728 Cores',
      'B? Nh? VRAM': '16GB GDDR6 256-bit',
      'TDP T?i �a': 'TGP 175W max',
      'Linh Ki?n Ray-Tracing': 'Nh�n RT th? h? 3'
    },
    score: 99,
    color: '#ec4899'
  },
  {
    id: 'ram',
    name: 'RAM DDR5 Dual-Channel 64GB',
    emoji: '?',
    sub: 'B? Nh? Si�u Bang Th�ng C?c Nhanh',
    desc: 'H? th?ng b? nh? dung lu?ng kh?ng 64GB DDR5 Dual Channel ch?y ? bus c?c cao 5600MHz, d? tr? c?c th?p. Cho ph�p render video 4K song song choi game AAA v� ch?y h�ng ch?c tab Chrome kh�ng gi?t lag.',
    specs: {
      'Dung Lu?ng': '64GB (2 x 32GB)',
      'Chu?n RAM': 'DDR5 SODIMM',
      'T?c �? Bus': '5600 MT/s',
      '�? Tr? Latency': 'CL40 - C?c th?p'
    },
    score: 95,
    color: '#a855f7'
  },
  {
    id: 'ssd',
    name: 'SSD PCIe Gen 5 NVMe 2TB',
    emoji: '??',
    sub: '? C?ng Si�u T?c Th? H? M?i Nh?t',
    desc: '? c?ng th? r?n cao c?p nh?t th? gi?i v?i chu?n PCIe Gen 5.0 x4 m?i nh?t, mang l?i t?c d? d?c ghi phi thu?ng l�n d?n 12,000 MB/s. Load game n?ng ch? trong t�ch t?c, kh?i d?ng Windows chua d?y 3 gi�y.',
    specs: {
      'T?c �? �?c': 'T?i 12,400 MB/s',
      'T?c �? Ghi': 'T?i 11,800 MB/s',
      'Chu?n K?t N?i': 'M.2 NVMe PCIe 5.0',
      '�? B?n TBW': '1400 TBW c?c tr�u'
    },
    score: 97,
    color: '#10b981'
  },
  {
    id: 'cooling',
    name: 'T?n Nhi?t Dual-Turbo Liquid-Metal',
    emoji: '??',
    sub: 'H? Th?ng L�m M�t Keo Kim Liquid-Metal',
    desc: 'S? d?ng keo kim lo?i l?ng Thermal Grizzly th? h? m?i tr�n b? m?t CPU/GPU gi�p h? nhi?t d? d?n 15�C so v?i keo truy?n th?ng. K?t h?p 2 qu?t c�nh th�p m?ng 0.1mm tang lu?ng gi� 35% m� kh�ng ?n.',
    specs: {
      'S? Qu?t / ?ng �?ng': '2 Qu?t / 7 ?ng d?n nhi?t',
      'Luu Lu?ng Gi�': '32.5 CFM',
      '�? ?n T?i �a': 'Du?i 42 dB',
      'V?t Li?u T?n': 'Keo kim lo?i l?ng + �?ng c�nh sen'
    },
    score: 92,
    color: '#3b82f6'
  },
  {
    id: 'battery',
    name: 'Pin VinaVolt 99.9Wh 4-Cell',
    emoji: '??',
    sub: 'Ngu?n Nang Lu?ng �?t Chu?n H�ng Kh�ng',
    desc: 'Dung lu?ng pin l?n nh?t th? gi?i được ph�p mang l�n m�y bay (99.9 Watt-hour). S? d?ng l�i Lithium-Polymer cao c?p cho th?i gian s? d?ng van ph�ng l�n d?n 10 ti?ng li�n t?c. T�ch h?p s?c nhanh VinaCharge 100W.',
    specs: {
      'Dung Lu?ng Wh': '99.9 Watt-hours',
      'S? L�i Pin': '4-Cell Lithium-Polymer',
      'C�ng Su?t S?c': 'S?c nhanh PD 100W',
      'Tu?i Th? V�ng �?i': 'Hon 1000 chu k? s?c'
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
  completeQuest(25, 'Kh�m ph� v� qu�t so d? 3D linh ki?n Laptop');
};

const chassisColors = [
  { id: 'black', name: 'Stealth Matte Black (�en m?)', grad: 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)' },
  { id: 'silver', name: 'Liquid Titanium Silver (B?c titan)', grad: 'linear-gradient(135deg, #cbd5e1 0%, #64748b 100%)' },
  { id: 'cyan', name: 'Quantum Cyber Cyan (Xanh Lazer)', grad: 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' },
  { id: 'purple', name: 'Obsidian Aurora Purple (T�m huy?n ?o)', grad: 'linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%)' }
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
  { id: 'stk-1', name: 'VinaTech Cyber', icon: '??' },
  { id: 'stk-2', name: 'Zero Bugs Coder', icon: '??' },
  { id: 'stk-3', name: 'RGB Gaming Beast', icon: '??' },
  { id: 'stk-4', name: 'Developer Coder', icon: '??' },
  { id: 'stk-5', name: 'Space Astronaut', icon: '?????' },
  { id: 'stk-6', name: 'Coffee Refueled', icon: '?' }
];

const appliedStickers = ref([
  { id: 'default-stk', name: 'VinaTech Cyber', icon: '??', x: 42, y: 35, scale: 1.2, rotate: 15 }
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
  { id: 'rainbow', name: 'Rainbow Wave (D?i m�u)', emoji: '??', desc: 'Ch?y d?i m�u chuy?n d?ng l?p l�nh li�n t?c' },
  { id: 'breathe', name: 'Breathing Glow (Nh?p th?)', emoji: '??', desc: '��n LED nh?p nh�y t?a s�ng theo chu k? nh?p th?' },
  { id: 'static', name: 'Static Neon (M�u tinh)', emoji: '??', desc: 'Gi? s�ng c? d?nh theo m�u s?c b?n pha tr?n' },
  { id: 'off', name: 'Backlit Off (T?t d�n)', emoji: '??', desc: 'T?t to�n b? h? th?ng d�n n?n b�n ph�m' }
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
    title: '�� luu thi?t k?! ??',
    text: 'C?u h�nh thi?t k? laptop d?c quy?n của bạn d� được ghi l?i th�nh c�ng.',
    icon: 'success',
    confirmButtonText: '��ng',
    confirmButtonColor: '#2563eb'
  });
  completeQuest(15, 'Tr?i nghi?m c� nh�n h�a Laptop');
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
  { name: 'Nguy?n Van H�ng', title: '�?i s? Kim Cuong', refs: 142, com: 42600000, avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Felix' },
  { name: 'Tr?n Th? Mai', title: '�?i s? V�ng', refs: 89, com: 26700000, avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Mia' },
  { name: 'Ph?m Minh Tu?n', title: '�?i s? V�ng', refs: 74, com: 22200000, avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Jack' },
  { name: 'L� Thanh Vy', title: '�?i s? B?c', refs: 45, com: 13500000, avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Zoe' },
  { name: 'Ho�ng Qu?c B?o', title: '�?i s? B?c', refs: 38, com: 11400000, avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Buddy' }
];

const rewardsShop = [
  { id: 'rew-1', name: 'Voucher Tr? Gi� 200,000d mua Laptop', emoji: '??', cost: 100 },
  { id: 'rew-2', name: 'M� Mi?n Ph� V?n Chuy?n VVIP To�n Qu?c', emoji: '??', cost: 50 },
  { id: 'rew-3', name: 'B? 6 Sticker Kim Lo?i Predator �?c Quy?n', emoji: '??', cost: 25 }
];

// Ambassador Live Ticker simulator
const recentPayouts = [
  '�?i s? Tr?n Th? Mai v?a nh?n +450,000d r�t ti?n hoa h?ng li�n k?t!',
  '�?i s? L� Thanh Vy v?a ghi nh?n don h�ng m?i ph�t sinh hoa h?ng +350,000d!',
  '�?i s? Nguy?n Van H�ng v?a nh?n gi?i thu?ng Top 1 �?i s? li�n k?t xu?t s?c +1,000,000d!',
  'C?ng t�c vi�n Ph?m Minh Tu?n v?a chuy?n th�nh c�ng don h�ng Laptop Gaming!'
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
    title: `+${amount} VinaCoins! ??`,
    text: `Ch�c m?ng b?n d� ho�n th�nh nhi?m v?: "${questName}"`,
    icon: 'success',
    confirmButtonColor: '#2563eb'
  });
};

const completeShareQuest = () => {
  const refLink = `${window.location.origin}/?ref=VINATECH-AMB`;
  navigator.clipboard.writeText(refLink).then(() => {
    Swal.fire({
      title: '�� sao ch�p link Ref! ??',
      text: 'Link tiếp thị d� được luu v�o khay nh? t?m. H�y d�n chia s? l�n Facebook/Zalo nh�!',
      icon: 'info',
      confirmButtonColor: '#2563eb'
    }).then(() => {
      completeQuest(20, 'Chia s? du?ng d?n Ti?p th? li�n k?t');
    });
  });
};

const completeNewsQuest = () => {
  completeQuest(10, 'T�m hi?u tin t?c c�ng ngh?');
};

const completeReferralQuest = () => {
  Swal.fire({
    title: 'M?i b?n b� gh� tham ??',
    text: 'Nh?p h? t�n ngu?i b?n mu?n gi?i thi?u mua Laptop:',
    input: 'text',
    inputPlaceholder: 'V� d?: Nguy?n Van A',
    showCancelButton: true,
    confirmButtonText: 'G?i l?i m?i',
    confirmButtonColor: '#2563eb',
    cancelButtonText: 'H?y'
  }).then((result) => {
    if (result.isConfirmed && result.value.trim()) {
      completeQuest(50, `Gi?i thi?u th�nh vi�n m?i: ${result.value}`);
    }
  });
};

const redeemReward = (reward) => {
  if (vinaCoins.value < reward.cost) {
    Swal.fire({
      title: 'Kh�ng d? VinaCoins! ??',
      text: `B?n c?n th�m ${reward.cost - vinaCoins.value} xu d? d?i ph?n qu� n�y.`,
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
    title: '�?i Qu� Th�nh C�ng! ??',
    html: `B?n d� d?i th�nh c�ng <strong>${reward.name}</strong>.<br/>M� Voucher của bạn: <strong style="color: #2563eb; font-size: 18px; font-family: monospace;">${code}</strong>`,
    icon: 'success',
    confirmButtonColor: '#2563eb'
  });
};



// Lifecycle Hooks
onMounted(() => {
  try {
    const cached = localStorage.getItem('predator_labs_real_products');
    if (cached) {
      realProducts.value = JSON.parse(cached);
    }
  } catch (e) {}

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

/* Subtle Background Elements */
.subtle-grid-bg {
  position: fixed;
  top: 0; left: 0; width: 100vw; height: 100vh;
  background-image:
    linear-gradient(rgba(15, 23, 42, 0.97), rgba(15, 23, 42, 0.97)),
    linear-gradient(to right, rgba(255, 255, 255, 0.015) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
  background-size: 100% 100%, 45px 45px, 45px 45px;
  z-index: -2;
  pointer-events: none;
}
.subtle-glow {
  position: fixed;
  width: 50vw; height: 50vw;
  border-radius: 50%;
  filter: blur(140px);
  opacity: 0.08;
  z-index: -2;
  pointer-events: none;
}
.subtle-glow.cyan { top: -10%; right: -10%; background: radial-gradient(circle, #3b82f6 0%, transparent 70%); }
.subtle-glow.purple { bottom: -10%; left: -10%; background: radial-gradient(circle, #6366f1 0%, transparent 70%); }

/* 1. HERO HEADER AREA */
.labs-hero {
  position: relative;
  background: radial-gradient(circle at top, rgba(37, 99, 235, 0.08) 0%, rgba(15, 23, 42, 0) 70%);
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
  font-size: 42px;
  font-weight: 800;
  margin: 0 0 15px 0;
  background: linear-gradient(135deg, #ffffff 40%, #94a3b8 100%);
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
  content: '?';
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
  color: #94a3b8;
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
  color: #94a3b8;
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
