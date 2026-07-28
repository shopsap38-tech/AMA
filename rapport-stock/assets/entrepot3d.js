/* Entrepôt 3D — visualisation de l'occupation en palettes (Three.js r128).
   Lit window.WAREHOUSE_DATA fourni par entrepot3d.php. */
(function () {
  'use strict';

  var D = window.WAREHOUSE_DATA;
  if (!D || typeof THREE === 'undefined') { return; }

  var container = document.getElementById('scene');
  if (!container) { return; }

  // --- Paramètres de disposition ---
  var LEVELS = D.levels || 3;
  var slotW = 1.1, slotH = 1.2, slotD = 1.1;
  var aisleGap = 1.6;          // espace (allée) tous les 2 rangs
  var occupied = D.slots.length;
  var total = Math.max(D.capacity, occupied);
  var columns = Math.max(1, Math.ceil(total / LEVELS));
  var perRow = Math.max(6, Math.round(Math.sqrt(columns * 2)));
  var rows = Math.ceil(columns / perRow);

  function colPos(col) {
    var r = Math.floor(col / perRow);
    var cInRow = col % perRow;
    return {
      x: cInRow * slotW,
      z: r * slotD + Math.floor(r / 2) * aisleGap
    };
  }
  function slotPos(i) {
    var col = Math.floor(i / LEVELS);
    var level = i % LEVELS;
    var p = colPos(col);
    return { x: p.x, y: level * slotH + slotH / 2, z: p.z };
  }

  // Centre de la scène (pour recentrer la caméra)
  var extentX = perRow * slotW;
  var extentZ = rows * slotD + Math.floor(rows / 2) * aisleGap;
  var cx = extentX / 2, cz = extentZ / 2;

  // --- Scène / caméra / rendu ---
  var scene = new THREE.Scene();
  scene.background = new THREE.Color(0xeef1f4);

  var w = container.clientWidth, h = container.clientHeight || 600;
  var camera = new THREE.PerspectiveCamera(50, w / h, 0.1, 5000);
  var dist = Math.max(extentX, extentZ) * 1.1 + 12;
  camera.position.set(cx + dist * 0.6, dist * 0.7, cz + dist);

  var renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setPixelRatio(window.devicePixelRatio || 1);
  renderer.setSize(w, h);
  container.appendChild(renderer.domElement);

  var controls = new THREE.OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.08;
  controls.target.set(cx, LEVELS * slotH / 2, cz);
  controls.autoRotate = true;
  controls.autoRotateSpeed = 0.8;
  controls.update();

  // Lumières
  scene.add(new THREE.HemisphereLight(0xffffff, 0x8899aa, 0.95));
  var dir = new THREE.DirectionalLight(0xffffff, 0.6);
  dir.position.set(cx + 20, 40, cz + 20);
  scene.add(dir);

  // Sol
  var floorGeo = new THREE.PlaneGeometry(extentX + 12, extentZ + 12);
  var floorMat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 1 });
  var floor = new THREE.Mesh(floorGeo, floorMat);
  floor.rotation.x = -Math.PI / 2;
  floor.position.set(cx, 0, cz);
  scene.add(floor);
  var grid = new THREE.GridHelper(Math.max(extentX, extentZ) + 12, Math.max(perRow, rows) + 6, 0xced4da, 0xe9ecef);
  grid.position.set(cx, 0.01, cz);
  scene.add(grid);

  // --- Bâtis de rack (fil de fer) par colonne ---
  var bayMat = new THREE.LineBasicMaterial({ color: 0x8a97a5 });
  for (var c = 0; c < columns; c++) {
    var p = colPos(c);
    var g = new THREE.BoxGeometry(slotW * 0.98, LEVELS * slotH, slotD * 0.98);
    var edges = new THREE.LineSegments(new THREE.EdgesGeometry(g), bayMat);
    edges.position.set(p.x, LEVELS * slotH / 2, p.z);
    scene.add(edges);
    g.dispose();
  }

  // --- Palettes occupées (boîtes colorées) ---
  var boxGeo = new THREE.BoxGeometry(slotW * 0.82, slotH * 0.82, slotD * 0.82);
  var mats = D.categories.map(function (c) {
    return new THREE.MeshStandardMaterial({ color: new THREE.Color(c.color), roughness: 0.6, metalness: 0.05 });
  });
  var meshesByCat = {};
  for (var i = 0; i < occupied; i++) {
    var catIdx = D.slots[i];
    var pos = slotPos(i);
    var m = new THREE.Mesh(boxGeo, mats[catIdx]);
    m.position.set(pos.x, pos.y, pos.z);
    m.userData = { cat: catIdx };
    scene.add(m);
    (meshesByCat[catIdx] = meshesByCat[catIdx] || []).push(m);
  }

  // Recentrage caméra final
  controls.target.set(cx, LEVELS * slotH / 2, cz);
  controls.update();

  // --- Interactions UI ---
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
      camera.position.set(cx + dist * 0.6, dist * 0.7, cz + dist);
      controls.target.set(cx, LEVELS * slotH / 2, cz);
      controls.update();
    });
  }

  // Filtres par catégorie (chips avec data-cat ; -1 = tout)
  var chips = document.querySelectorAll('[data-cat]');
  Array.prototype.forEach.call(chips, function (chip) {
    chip.addEventListener('click', function () {
      var sel = parseInt(chip.getAttribute('data-cat'), 10);
      Array.prototype.forEach.call(chips, function (c) { c.classList.remove('active'); });
      chip.classList.add('active');
      Object.keys(meshesByCat).forEach(function (k) {
        var visible = (sel === -1) || (parseInt(k, 10) === sel);
        meshesByCat[k].forEach(function (m) { m.visible = visible; });
      });
    });
  });

  // Survol : info-bulle catégorie
  var tip = document.getElementById('scene-tip');
  var ray = new THREE.Raycaster();
  var mouse = new THREE.Vector2();
  renderer.domElement.addEventListener('mousemove', function (e) {
    if (!tip) { return; }
    var rect = renderer.domElement.getBoundingClientRect();
    mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
    ray.setFromCamera(mouse, camera);
    var hits = ray.intersectObjects(scene.children.filter(function (o) { return o.userData && o.userData.cat != null; }));
    if (hits.length) {
      var cat = D.categories[hits[0].object.userData.cat];
      tip.style.display = 'block';
      tip.style.left = (e.clientX - rect.left + 12) + 'px';
      tip.style.top = (e.clientY - rect.top + 12) + 'px';
      tip.textContent = cat.name + ' — ' + cat.count + ' palette(s)';
    } else {
      tip.style.display = 'none';
    }
  });
  renderer.domElement.addEventListener('mouseleave', function () {
    if (tip) { tip.style.display = 'none'; }
  });

  // --- Boucle de rendu ---
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
