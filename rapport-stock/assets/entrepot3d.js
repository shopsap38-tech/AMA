/* Entrepôt 3D — visualisation de l'occupation en palettes (Three.js r128).
   Rendu INSTANCIÉ (InstancedMesh) pour supporter de grandes capacités
   (plusieurs milliers d'emplacements). Lit window.WAREHOUSE_DATA. */
(function () {
  'use strict';

  var D = window.WAREHOUSE_DATA;
  if (!D || typeof THREE === 'undefined') { return; }

  var container = document.getElementById('scene');
  if (!container) { return; }

  // --- Disposition ---
  var LEVELS = D.levels || 3;
  var slotW = 1.1, slotH = 1.2, slotD = 1.1;
  var aisleGap = 1.8;                       // allée tous les 2 rangs
  var occupiedCount = D.slots.length;
  var capacity = Math.max(D.capacity, 0);
  var total = Math.max(capacity, occupiedCount);
  var freeCount = Math.max(capacity - occupiedCount, 0);
  var columns = Math.max(1, Math.ceil(total / LEVELS));
  var perRow = Math.max(8, Math.round(Math.sqrt(columns * 2)));
  var rows = Math.ceil(columns / perRow);

  function slotPos(i) {
    var col = Math.floor(i / LEVELS);
    var level = i % LEVELS;
    var r = Math.floor(col / perRow);
    var cInRow = col % perRow;
    return {
      x: cInRow * slotW,
      y: level * slotH + slotH / 2,
      z: r * slotD + Math.floor(r / 2) * aisleGap
    };
  }

  var extentX = perRow * slotW;
  var extentZ = rows * slotD + Math.floor(rows / 2) * aisleGap;
  var cx = extentX / 2, cz = extentZ / 2;

  // --- Scène / caméra / rendu ---
  var scene = new THREE.Scene();
  scene.background = new THREE.Color(0xeef1f4);

  var w = container.clientWidth, h = container.clientHeight || 600;
  var camera = new THREE.PerspectiveCamera(50, w / h, 0.1, 100000);
  var dist = Math.max(extentX, extentZ) * 0.9 + 14;
  function defaultCam() {
    camera.position.set(cx + dist * 0.55, dist * 0.75, cz + dist);
  }
  defaultCam();

  var renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  renderer.setSize(w, h);
  container.appendChild(renderer.domElement);

  var controls = new THREE.OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.08;
  controls.target.set(cx, LEVELS * slotH / 2, cz);
  controls.autoRotate = true;
  controls.autoRotateSpeed = 0.7;
  controls.update();

  // Lumières
  scene.add(new THREE.HemisphereLight(0xffffff, 0x8899aa, 1.0));
  var dl = new THREE.DirectionalLight(0xffffff, 0.55);
  dl.position.set(cx + 30, 60, cz + 30);
  scene.add(dl);

  // Sol + grille
  var floor = new THREE.Mesh(
    new THREE.PlaneGeometry(extentX + 16, extentZ + 16),
    new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 1 })
  );
  floor.rotation.x = -Math.PI / 2;
  floor.position.set(cx, 0, cz);
  scene.add(floor);
  var grid = new THREE.GridHelper(Math.max(extentX, extentZ) + 16,
    Math.max(perRow, rows) + 6, 0xced4da, 0xe9ecef);
  grid.position.set(cx, 0.01, cz);
  scene.add(grid);

  // --- Palettes occupées (InstancedMesh coloré) ---
  var boxGeo = new THREE.BoxGeometry(slotW * 0.82, slotH * 0.82, slotD * 0.82);
  var occMat = new THREE.MeshStandardMaterial({ roughness: 0.6, metalness: 0.05 });
  var occMesh = new THREE.InstancedMesh(boxGeo, occMat, Math.max(occupiedCount, 1));
  occMesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);

  var catColors = (D.categories || []).map(function (c) { return new THREE.Color(c.color); });
  var alertColors = (D.alertes || []).map(function (a) { return new THREE.Color(a.color); });

  var dummy = new THREE.Object3D();
  var catOf = D.slots;
  var alertOf = D.slotsAlert || [];

  // Répartit les palettes occupées sur toute l'emprise de l'entrepôt (au lieu
  // d'un bloc compact) pour un rendu réaliste, même à faible taux d'occupation.
  var slotIndexForOcc = new Array(occupiedCount);
  var step = occupiedCount > 0 ? (total / occupiedCount) : 1;
  for (var i = 0; i < occupiedCount; i++) {
    slotIndexForOcc[i] = Math.min(total - 1, Math.round(i * step));
  }

  function placeOcc(k, visible) {
    var p = slotPos(slotIndexForOcc[k]);
    dummy.position.set(p.x, visible ? p.y : -1000, p.z);
    dummy.scale.set(1, visible ? 1 : 0.0001, 1);
    dummy.updateMatrix();
    occMesh.setMatrixAt(k, dummy.matrix);
  }

  for (var k0 = 0; k0 < occupiedCount; k0++) {
    placeOcc(k0, true);
    occMesh.setColorAt(k0, catColors[catOf[k0]] || new THREE.Color(0x888888));
  }
  occMesh.instanceMatrix.needsUpdate = true;
  if (occMesh.instanceColor) { occMesh.instanceColor.needsUpdate = true; }
  scene.add(occMesh);

  // --- Emplacements libres ---
  // Rendus en boîtes translucides seulement si peu nombreux (sinon la grille au
  // sol suffit et l'on évite une nappe opaque + un surcoût de rendu).
  var freeMesh = null;
  if (freeCount > 0 && freeCount <= 3000) {
    var used = {};
    for (var u = 0; u < occupiedCount; u++) { used[slotIndexForOcc[u]] = 1; }
    var freePositions = [];
    for (var s = 0; s < total && freePositions.length < freeCount; s++) {
      if (!used[s]) { freePositions.push(s); }
    }
    var freeMat = new THREE.MeshStandardMaterial({
      color: 0xb9c2cc, roughness: 1, transparent: true, opacity: 0.18
    });
    freeMesh = new THREE.InstancedMesh(boxGeo, freeMat, freePositions.length);
    for (var f = 0; f < freePositions.length; f++) {
      var pf = slotPos(freePositions[f]);
      dummy.position.set(pf.x, pf.y, pf.z);
      dummy.scale.set(1, 1, 1);
      dummy.updateMatrix();
      freeMesh.setMatrixAt(f, dummy.matrix);
    }
    freeMesh.instanceMatrix.needsUpdate = true;
    scene.add(freeMesh);
  }

  controls.target.set(cx, LEVELS * slotH / 2, cz);
  controls.update();

  // --- Couleur : catégorie / alerte ---
  function setColorMode(mode) {
    var colors = (mode === 'alert') ? alertColors : catColors;
    var keyOf = (mode === 'alert') ? alertOf : catOf;
    for (var k = 0; k < occupiedCount; k++) {
      occMesh.setColorAt(k, colors[keyOf[k]] || new THREE.Color(0x888888));
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

  // --- Filtre par catégorie (masque en déplaçant hors champ) ---
  function applyFilter(sel) {
    for (var k = 0; k < occupiedCount; k++) {
      placeOcc(k, (sel === -1) || (catOf[k] === sel));
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

  // --- Contrôles ---
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
      defaultCam();
      controls.target.set(cx, LEVELS * slotH / 2, cz);
      controls.update();
    });
  }

  // --- Survol : info-bulle (instanceId) ---
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
      var id = hit[0].instanceId;
      var cat = D.categories[catOf[id]];
      tip.style.display = 'block';
      tip.style.left = (e.clientX - rect.left + 12) + 'px';
      tip.style.top = (e.clientY - rect.top + 12) + 'px';
      tip.textContent = cat ? (cat.name + ' — ' + cat.count + ' palette(s)') : 'palette';
    } else {
      tip.style.display = 'none';
    }
  });
  renderer.domElement.addEventListener('mouseleave', function () {
    if (tip) { tip.style.display = 'none'; }
  });

  // --- Rendu ---
  function animate() {
    requestAnimationFrame(animate);
    controls.update();
    renderer.render(scene, camera);
  }
  animate();

  window.addEventListener('resize', function () {
    var W = container.clientWidth, H = container.clientHeight || 600;
    camera.aspect = W / H;
    camera.updateProjectionMatrix();
    renderer.setSize(W, H);
  });
})();
