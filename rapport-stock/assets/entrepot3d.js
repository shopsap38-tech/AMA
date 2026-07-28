/* Entrepôt 3D — rayonnages (racks) multi-niveaux façon entrepôt réel.
   Three.js r128, rendu instancié. Lit window.WAREHOUSE_DATA. */
(function () {
  'use strict';

  var D = window.WAREHOUSE_DATA;
  var container = document.getElementById('scene');
  if (!container) { return; }
  if (!D || D.error || typeof THREE === 'undefined') { return; }

  try {

  // ---- Paramètres de rayonnage ----
  var LEVELS = 4;            // niveaux par rack
  var WIDE   = 2;            // palettes de front par alvéole
  var slotW = 1.05, slotH = 1.35, slotD = 1.15;
  var bayGap = 0.18;         // jeu entre alvéoles (montant)
  var aisleW = 2.4;          // largeur d'allée
  var baysPerLine = 12;      // alvéoles par ligne de rack

  var MAXBOX = 8000;
  var occupiedCount = Math.min(D.slots.length, MAXBOX);
  var perBayCol = LEVELS * WIDE;
  var baysNeeded = Math.max(1, Math.ceil(occupiedCount / perBayCol));
  var linesNeeded = Math.ceil(baysNeeded / baysPerLine);

  var bayW = WIDE * slotW;
  var pitchX = bayW + bayGap;
  var blockDepth = 2 * slotD + aisleW;   // 2 lignes dos à dos + allée

  function palletPos(i) {
    var bay = Math.floor(i / perBayCol);
    var inBay = i % perBayCol;
    var level = Math.floor(inBay / WIDE);
    var wpos = inBay % WIDE;
    var line = Math.floor(bay / baysPerLine);
    var bayInLine = bay % baysPerLine;
    var block = Math.floor(line / 2);
    var side = line % 2;
    return {
      x: bayInLine * pitchX + wpos * slotW + slotW / 2,
      y: level * slotH + slotH / 2 + 0.05,
      z: block * blockDepth + side * slotD + slotD / 2
    };
  }

  // Emprise
  var extentX = baysPerLine * pitchX;
  var blocks = Math.ceil(linesNeeded / 2);
  var extentZ = blocks * blockDepth;
  var cx = extentX / 2, cz = extentZ / 2, topY = LEVELS * slotH;

  // ---- Scène ----
  var scene = new THREE.Scene();
  scene.background = new THREE.Color(0xeef1f4);
  var w = container.clientWidth, h = container.clientHeight || 600;
  var camera = new THREE.PerspectiveCamera(50, w / h, 0.1, 100000);
  var dist = Math.max(extentX, extentZ) * 0.85 + 16;
  function defaultCam() { camera.position.set(cx - dist * 0.35, topY + dist * 0.6, cz + dist); }
  defaultCam();

  var renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  renderer.setSize(w, h);
  container.appendChild(renderer.domElement);

  var controls = new THREE.OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.08;
  controls.target.set(cx, topY / 2, cz);
  controls.autoRotate = true;
  controls.autoRotateSpeed = 0.6;
  controls.update();

  scene.add(new THREE.HemisphereLight(0xffffff, 0x8899aa, 1.05));
  var dl = new THREE.DirectionalLight(0xffffff, 0.5);
  dl.position.set(cx + 30, 80, cz + 40);
  scene.add(dl);

  // Sol
  var floor = new THREE.Mesh(
    new THREE.PlaneGeometry(extentX + 20, extentZ + 20),
    new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 1 })
  );
  floor.rotation.x = -Math.PI / 2;
  floor.position.set(cx, 0, cz);
  scene.add(floor);

  // ---- Structure des racks (montants + lisses) en InstancedMesh ----
  var postMat = new THREE.MeshStandardMaterial({ color: 0x1f3a5f, roughness: 0.5, metalness: 0.4 });
  var beamMat = new THREE.MeshStandardMaterial({ color: 0xff7a1a, roughness: 0.5, metalness: 0.3 });

  var posts = [];   // {x,z}
  var beams = [];   // {x,y,z,len}
  for (var line = 0; line < linesNeeded; line++) {
    var block = Math.floor(line / 2), side = line % 2;
    var zc = block * blockDepth + side * slotD + slotD / 2;
    var baysThis = Math.min(baysPerLine, baysNeeded - line * baysPerLine);
    if (baysThis <= 0) { break; }
    // montants verticaux aux bords d'alvéole (avant/arrière)
    for (var b = 0; b <= baysThis; b++) {
      var xp = b * pitchX - bayGap / 2;
      posts.push({ x: xp, z: zc - slotD / 2 });
      posts.push({ x: xp, z: zc + slotD / 2 });
    }
    // lisses horizontales par niveau (avant/arrière)
    var lineLen = baysThis * pitchX;
    for (var lv = 0; lv <= LEVELS; lv++) {
      var yb = lv * slotH;
      beams.push({ x: lineLen / 2 - bayGap / 2, y: yb, z: zc - slotD / 2, len: lineLen });
      beams.push({ x: lineLen / 2 - bayGap / 2, y: yb, z: zc + slotD / 2, len: lineLen });
    }
  }

  var dummy = new THREE.Object3D();
  var postGeo = new THREE.BoxGeometry(0.08, topY, 0.08);
  var postMesh = new THREE.InstancedMesh(postGeo, postMat, posts.length);
  for (var pi = 0; pi < posts.length; pi++) {
    dummy.position.set(posts[pi].x, topY / 2, posts[pi].z);
    dummy.scale.set(1, 1, 1); dummy.rotation.set(0, 0, 0); dummy.updateMatrix();
    postMesh.setMatrixAt(pi, dummy.matrix);
  }
  postMesh.instanceMatrix.needsUpdate = true;
  scene.add(postMesh);

  var beamGeo = new THREE.BoxGeometry(1, 0.06, 0.06);
  var beamMesh = new THREE.InstancedMesh(beamGeo, beamMat, beams.length);
  for (var bi = 0; bi < beams.length; bi++) {
    dummy.position.set(beams[bi].x, beams[bi].y, beams[bi].z);
    dummy.scale.set(beams[bi].len, 1, 1); dummy.rotation.set(0, 0, 0); dummy.updateMatrix();
    beamMesh.setMatrixAt(bi, dummy.matrix);
  }
  beamMesh.instanceMatrix.needsUpdate = true;
  scene.add(beamMesh);

  // ---- Palettes (InstancedMesh coloré) ----
  var boxGeo = new THREE.BoxGeometry(slotW * 0.86, slotH * 0.78, slotD * 0.82);
  var occMat = new THREE.MeshStandardMaterial({ roughness: 0.55, metalness: 0.05 });
  var occMesh = new THREE.InstancedMesh(boxGeo, occMat, Math.max(occupiedCount, 1));
  occMesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);

  var catColors = (D.categories || []).map(function (c) { return new THREE.Color(c.color); });
  var alertColors = (D.alertes || []).map(function (a) { return new THREE.Color(a.color); });
  var catOf = D.slots, alertOf = D.slotsAlert || [];

  function placeBox(k, visible) {
    var p = palletPos(k);
    dummy.position.set(p.x, visible ? p.y : -1000, p.z);
    dummy.scale.set(1, visible ? 1 : 0.0001, 1);
    dummy.rotation.set(0, 0, 0);
    dummy.updateMatrix();
    occMesh.setMatrixAt(k, dummy.matrix);
  }
  for (var k = 0; k < occupiedCount; k++) {
    placeBox(k, true);
    occMesh.setColorAt(k, catColors[catOf[k]] || new THREE.Color(0x888888));
  }
  occMesh.instanceMatrix.needsUpdate = true;
  if (occMesh.instanceColor) { occMesh.instanceColor.needsUpdate = true; }
  scene.add(occMesh);

  // ---- Numéros d'allée (sprites sur le sol) ----
  function labelSprite(text) {
    var c = document.createElement('canvas'); c.width = 96; c.height = 64;
    var ctx = c.getContext('2d');
    ctx.fillStyle = '#334'; ctx.font = 'bold 46px Arial'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.fillText(text, 48, 34);
    var tex = new THREE.CanvasTexture(c);
    var sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true, depthTest: false }));
    sp.scale.set(1.3, 0.85, 1);
    return sp;
  }
  var nBays = Math.min(baysPerLine, baysNeeded);
  for (var bn = 0; bn < nBays; bn++) {
    var s = labelSprite(('0' + (bn + 1)).slice(-2));
    s.position.set(bn * pitchX + bayW / 2, 0.15, -1.1);
    scene.add(s);
  }

  controls.target.set(cx, topY / 2, cz);
  controls.update();

  // ---- Couleur : catégorie / alerte ----
  function setColorMode(mode) {
    var colors = (mode === 'alert') ? alertColors : catColors;
    var keyOf = (mode === 'alert') ? alertOf : catOf;
    for (var j = 0; j < occupiedCount; j++) {
      occMesh.setColorAt(j, colors[keyOf[j]] || new THREE.Color(0x888888));
    }
    if (occMesh.instanceColor) { occMesh.instanceColor.needsUpdate = true; }
  }
  var mCat = document.getElementById('mode-cat');
  var mAlert = document.getElementById('mode-alert');
  var chipsCat = document.getElementById('chips-cat');
  var legAlert = document.getElementById('legend-alert');
  if (mCat && mAlert) {
    mCat.addEventListener('click', function () {
      setColorMode('cat'); mCat.classList.add('active'); mAlert.classList.remove('active');
      if (chipsCat) { chipsCat.style.display = ''; }
      if (legAlert) { legAlert.style.display = 'none'; }
    });
    mAlert.addEventListener('click', function () {
      setColorMode('alert'); mAlert.classList.add('active'); mCat.classList.remove('active');
      if (chipsCat) { chipsCat.style.display = 'none'; }
      if (legAlert) { legAlert.style.display = 'flex'; }
    });
  }

  // ---- Filtre par catégorie ----
  function applyFilter(sel) {
    for (var j = 0; j < occupiedCount; j++) {
      placeBox(j, (sel === -1) || (catOf[j] === sel));
    }
    occMesh.instanceMatrix.needsUpdate = true;
  }
  var chips = document.querySelectorAll('[data-cat]');
  Array.prototype.forEach.call(chips, function (chip) {
    chip.addEventListener('click', function () {
      var sel = parseInt(chip.getAttribute('data-cat'), 10);
      Array.prototype.forEach.call(chips, function (c) { c.classList.remove('active'); });
      chip.classList.add('active');
      applyFilter(sel);
    });
  });

  // ---- Contrôles ----
  var btnRotate = document.getElementById('btn-rotate');
  if (btnRotate) {
    btnRotate.addEventListener('click', function () {
      controls.autoRotate = !controls.autoRotate;
      btnRotate.classList.toggle('active', controls.autoRotate);
    });
  }
  var btnReset = document.getElementById('btn-reset-view');
  if (btnReset) {
    btnReset.addEventListener('click', function () {
      defaultCam(); controls.target.set(cx, topY / 2, cz); controls.update();
    });
  }

  // ---- Survol ----
  var tip = document.getElementById('scene-tip');
  var ray = new THREE.Raycaster();
  var mouse = new THREE.Vector2();
  renderer.domElement.addEventListener('mousemove', function (e) {
    if (!tip) { return; }
    var rect = renderer.domElement.getBoundingClientRect();
    mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
    ray.setFromCamera(mouse, camera);
    var hit = ray.intersectObject(occMesh);
    if (hit.length && hit[0].instanceId != null) {
      var cat = D.categories[catOf[hit[0].instanceId]];
      tip.style.display = 'block';
      tip.style.left = (e.clientX - rect.left + 12) + 'px';
      tip.style.top = (e.clientY - rect.top + 12) + 'px';
      tip.textContent = cat ? (cat.name + ' — ' + cat.count + ' pal.') : 'palette';
    } else { tip.style.display = 'none'; }
  });
  renderer.domElement.addEventListener('mouseleave', function () {
    if (tip) { tip.style.display = 'none'; }
  });

  // ---- Rendu ----
  function animate() { requestAnimationFrame(animate); controls.update(); renderer.render(scene, camera); }
  animate();
  window.addEventListener('resize', function () {
    var W = container.clientWidth, H = container.clientHeight || 600;
    camera.aspect = W / H; camera.updateProjectionMatrix(); renderer.setSize(W, H);
  });

  } catch (err) {
    if (window.console) { console.error('Entrepôt 3D — erreur de rendu :', err); }
    container.innerHTML = '<div class="scene-error">⚠ <strong>Erreur lors du rendu 3D.</strong><br>'
      + ((err && err.message) ? err.message : err) + '<br>Détails dans la console (F12).</div>';
  }
})();
