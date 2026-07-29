/* Entrepôt 3D — visualisation WMS (type SAP EWM / Manhattan).
   Racks générés par zone, colorés par occupation, clic pour le détail.
   Three.js r128. Données via warehouse_api.php (repli : window.WAREHOUSE_FALLBACK). */
(function () {
  'use strict';

  var container = document.getElementById('scene');
  if (!container) { return; }
  var tip = document.getElementById('scene-tip');

  function showError(msg) {
    container.innerHTML = '<div class="scene-error">' + msg + '</div>';
  }

  function boot(data) {
    if (!data || data.error) {
      showError('⚠ <strong>Données indisponibles.</strong>' + (data && data.error ? '<br>' + data.error : ''));
      return;
    }
    if (typeof THREE === 'undefined') { return; }
    try { build(data); }
    catch (err) {
      if (window.console) { console.error('Entrepôt 3D :', err); }
      showError('⚠ <strong>Erreur de rendu 3D.</strong><br>' + ((err && err.message) ? err.message : err));
    }
  }

  // Chargement via l'API PHP, avec repli sur les données intégrées.
  if (window.fetch) {
    fetch('warehouse_api.php', { cache: 'no-store' })
      .then(function (r) { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
      .then(function (d) { updateDashboard(d); boot(d); })
      .catch(function () { boot(window.WAREHOUSE_FALLBACK); });
  } else {
    boot(window.WAREHOUSE_FALLBACK);
  }

  function fmt(n) { return (Math.round(n)).toLocaleString('fr-FR'); }

  function updateDashboard(d) {
    if (!d || d.error) { return; }
    var set = function (id, v) { var e = document.getElementById(id); if (e) { e.textContent = v; } };
    set('kpi-cap', fmt(d.capacite_totale));
    set('kpi-occ', fmt(d.occupees));
    set('kpi-libre', fmt(d.libres));
    set('kpi-taux', (d.taux).toString().replace('.', ',') + '%');
  }

  function rackColor(rate) {
    if (rate < 0.5) { return 0x2f9e44; } // vert
    if (rate < 0.8) { return 0xe8720c; } // orange
    return 0xc92a2a;                     // rouge
  }

  function textSprite(text, opts) {
    opts = opts || {};
    var fs = opts.fontSize || 44;
    var pad = opts.pad || 8;
    var cnv = document.createElement('canvas');
    var ctx = cnv.getContext('2d');
    ctx.font = 'bold ' + fs + 'px Arial';
    var w = Math.ceil(ctx.measureText(text).width) + pad * 2;
    var h = fs + pad * 2;
    cnv.width = w; cnv.height = h;
    ctx.font = 'bold ' + fs + 'px Arial';
    if (opts.bg) { ctx.fillStyle = opts.bg; roundRect(ctx, 0, 0, w, h, 8); ctx.fill(); }
    ctx.fillStyle = opts.color || '#1f2933';
    ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.fillText(text, w / 2, h / 2);
    var tex = new THREE.CanvasTexture(cnv);
    tex.minFilter = THREE.LinearFilter;
    var sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true, depthTest: opts.depthTest !== false }));
    var scale = (opts.scale || 0.01);
    sp.scale.set(w * scale, h * scale, 1);
    return sp;
  }
  function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }

  function build(data) {
    var racks = data.racks || [];
    var N = racks.length;

    // Regroupement par zone (ordre d'apparition).
    var order = [], byZone = {};
    racks.forEach(function (rk) {
      if (!byZone[rk.zone]) { byZone[rk.zone] = []; order.push(rk.zone); }
      byZone[rk.zone].push(rk);
    });

    // Dimensions.
    var rackW = 1.6, rackD = 1.1, maxH = 4.4;
    var pitchX = rackW + 0.5, pitchZ = rackD + 0.9;
    var zoneGap = 3.6, rowGap = 4.8, maxRowWidth = 92;

    // Placement des blocs-zones.
    var place = {}, cursorX = 0, cursorZ = 0, rowDepth = 0, maxX = 0;
    order.forEach(function (z) {
      var n = byZone[z].length;
      var cols = Math.max(1, Math.ceil(Math.sqrt(n * 1.7)));
      var rows = Math.ceil(n / cols);
      var w = cols * pitchX, d = rows * pitchZ;
      if (cursorX > 0 && cursorX + w > maxRowWidth) { cursorX = 0; cursorZ += rowDepth + rowGap; rowDepth = 0; }
      place[z] = { x0: cursorX, z0: cursorZ, cols: cols, rows: rows, w: w, d: d };
      cursorX += w + zoneGap; rowDepth = Math.max(rowDepth, d); maxX = Math.max(maxX, cursorX);
    });
    var totalW = maxX, totalD = cursorZ + rowDepth;
    var cx = totalW / 2, cz = totalD / 2;

    // Scène.
    var scene = new THREE.Scene();
    scene.background = new THREE.Color(0xeaeef2);
    scene.fog = new THREE.Fog(0xeaeef2, Math.max(totalW, totalD) * 1.2, Math.max(totalW, totalD) * 3);

    var w0 = container.clientWidth, h0 = container.clientHeight || 620;
    var camera = new THREE.PerspectiveCamera(48, w0 / h0, 0.1, 100000);
    var dist = Math.max(totalW, totalD) * 0.62 + 16;
    function defaultCam() { camera.position.set(cx - dist * 0.35, maxH + dist * 0.52, cz + dist * 0.95); }
    defaultCam();

    var renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(w0, h0);
    container.innerHTML = '';
    container.appendChild(renderer.domElement);

    var controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true; controls.dampingFactor = 0.08;
    controls.maxPolarAngle = Math.PI * 0.49;
    controls.target.set(cx, maxH / 2, cz);
    controls.autoRotate = true; controls.autoRotateSpeed = 0.45;
    controls.update();

    scene.add(new THREE.HemisphereLight(0xffffff, 0x9aa7b4, 1.0));
    var dl = new THREE.DirectionalLight(0xffffff, 0.55);
    dl.position.set(cx + 40, 90, cz + 50);
    scene.add(dl);

    // Sol + grille.
    var floor = new THREE.Mesh(
      new THREE.PlaneGeometry(totalW + 30, totalD + 30),
      new THREE.MeshStandardMaterial({ color: 0xf4f6f8, roughness: 1 })
    );
    floor.rotation.x = -Math.PI / 2; floor.position.set(cx, 0, cz);
    scene.add(floor);
    var grid = new THREE.GridHelper(Math.max(totalW, totalD) + 30, Math.round((Math.max(totalW, totalD) + 30) / 2), 0xccd4dc, 0xe4e9ee);
    grid.position.set(cx, 0.02, cz);
    scene.add(grid);

    // Positions des racks.
    var pos = new Array(N);
    var idx = 0;
    order.forEach(function (z) {
      var p = place[z], list = byZone[z];
      list.forEach(function (rk, i) {
        var col = i % p.cols, row = Math.floor(i / p.cols);
        pos[idx] = {
          x: p.x0 + col * pitchX + rackW / 2,
          z: p.z0 + row * pitchZ + rackD / 2,
          rk: rk
        };
        idx++;
      });
      // Étiquette de zone.
      var zl = textSprite('ZONE ' + z, { fontSize: 60, color: '#0a6ed1', bg: 'rgba(255,255,255,0.85)', scale: 0.02, depthTest: false });
      zl.position.set(p.x0 + p.w / 2 - zoneGap / 2, maxH + 2.2, p.z0 - 0.6);
      scene.add(zl);
    });

    // ---- Rayonnages réels : montants + lisses + palettes sur les niveaux ----
    var LEVELS = 4;          // niveaux de rack
    var PERLVL = 2;          // palettes de front par niveau
    var CELLS  = LEVELS * PERLVL;
    var cellW  = rackW / PERLVL;
    var cellH  = maxH / LEVELS;

    var dummy = new THREE.Object3D();
    var col   = new THREE.Color();

    // Nombre de palettes visibles par rack (remplissage selon le taux).
    var filled = new Array(N);
    var totalPallets = 0;
    for (var r = 0; r < N; r++) {
      var f = Math.round(pos[r].rk.rate * CELLS);
      f = Math.max(0, Math.min(CELLS, f));
      filled[r] = f;
      totalPallets += f;
    }

    // Coque invisible : cible de clic couvrant tout le rack.
    var shellGeo = new THREE.BoxGeometry(rackW * 1.02, maxH, rackD * 1.02);
    var shellMat = new THREE.MeshBasicMaterial({ transparent: true, opacity: 0.0, depthWrite: false });
    var shells = new THREE.InstancedMesh(shellGeo, shellMat, N);

    // Montants (4 par rack).
    var upGeo = new THREE.BoxGeometry(0.08, maxH, 0.08);
    var upMat = new THREE.MeshStandardMaterial({ color: 0x33455a, metalness: 0.45, roughness: 0.5 });
    var uprights = new THREE.InstancedMesh(upGeo, upMat, N * 4);

    // Lisses horizontales (avant/arrière) à chaque niveau + sommet.
    var beamGeo = new THREE.BoxGeometry(1, 0.06, 0.06);
    var beamMat = new THREE.MeshStandardMaterial({ color: 0xe8720c, metalness: 0.3, roughness: 0.5 });
    var beams = new THREE.InstancedMesh(beamGeo, beamMat, N * (LEVELS + 1) * 2);

    // Palettes (colorées par occupation du rack).
    var palGeo = new THREE.BoxGeometry(cellW * 0.82, cellH * 0.62, rackD * 0.72);
    var palMat = new THREE.MeshStandardMaterial({ roughness: 0.6, metalness: 0.04 });
    var pallets = new THREE.InstancedMesh(palGeo, palMat, Math.max(totalPallets, 1));

    var ui = 0, bi = 0, pi = 0;
    var cornX = [-rackW / 2, rackW / 2], cornZ = [-rackD / 2, rackD / 2];
    for (var r2 = 0; r2 < N; r2++) {
      var px = pos[r2].x, pz = pos[r2].z, rk2 = pos[r2].rk;
      var rc = rackColor(rk2.rate);

      // Coque (cible de clic).
      dummy.position.set(px, maxH / 2, pz); dummy.scale.set(1, 1, 1); dummy.rotation.set(0, 0, 0); dummy.updateMatrix();
      shells.setMatrixAt(r2, dummy.matrix);

      // Montants aux 4 coins.
      for (var a = 0; a < 2; a++) {
        for (var b = 0; b < 2; b++) {
          dummy.position.set(px + cornX[a], maxH / 2, pz + cornZ[b]);
          dummy.scale.set(1, 1, 1); dummy.rotation.set(0, 0, 0); dummy.updateMatrix();
          uprights.setMatrixAt(ui++, dummy.matrix);
        }
      }

      // Lisses (avant/arrière) à chaque niveau.
      for (var L = 0; L <= LEVELS; L++) {
        var yb = L * cellH;
        for (var s = 0; s < 2; s++) {
          dummy.position.set(px, yb + 0.02, pz + cornZ[s]);
          dummy.scale.set(rackW, 1, 1); dummy.rotation.set(0, 0, 0); dummy.updateMatrix();
          beams.setMatrixAt(bi++, dummy.matrix);
        }
      }

      // Palettes : remplissage bas -> haut, gauche -> droite.
      var nf = filled[r2];
      for (var i = 0; i < nf; i++) {
        var lvl = Math.floor(i / PERLVL);
        var c = i % PERLVL;
        var lx = (c + 0.5) * cellW - rackW / 2;
        var y = lvl * cellH + cellH * 0.42;
        dummy.position.set(px + lx, y, pz);
        dummy.scale.set(1, 1, 1); dummy.rotation.set(0, 0, 0); dummy.updateMatrix();
        pallets.setMatrixAt(pi, dummy.matrix);
        pallets.setColorAt(pi, col.set(rc));
        pi++;
      }
    }
    shells.instanceMatrix.needsUpdate = true;
    uprights.instanceMatrix.needsUpdate = true;
    beams.instanceMatrix.needsUpdate = true;
    pallets.instanceMatrix.needsUpdate = true;
    if (pallets.instanceColor) { pallets.instanceColor.needsUpdate = true; }
    scene.add(shells); scene.add(uprights); scene.add(beams); scene.add(pallets);

    // Étiquettes de code (togglables).
    var codeSprites = [];
    for (var c = 0; c < N; c++) {
      var s = textSprite(pos[c].rk.code, { fontSize: 30, color: '#32363a', bg: 'rgba(255,255,255,0.75)', scale: 0.009 });
      s.position.set(pos[c].x, maxH + 0.55, pos[c].z);
      scene.add(s); codeSprites.push(s);
    }

    // Surbrillance de sélection.
    var outline = new THREE.LineSegments(
      new THREE.EdgesGeometry(new THREE.BoxGeometry(rackW * 1.04, maxH * 1.02, rackD * 1.04)),
      new THREE.LineBasicMaterial({ color: 0x0a6ed1 })
    );
    outline.visible = false;
    scene.add(outline);

    // Panneau d'info.
    var elEmpty = document.getElementById('rack-empty');
    var elBody = document.getElementById('rack-body');
    function selectRack(r) {
      var pr = pos[r], rk = pr.rk;
      outline.position.set(pr.x, maxH / 2, pr.z); outline.visible = true;
      if (elEmpty) { elEmpty.style.display = 'none'; }
      if (elBody) { elBody.style.display = 'block'; }
      var pct = Math.round(rk.rate * 100);
      var color = '#' + rackColor(rk.rate).toString(16).padStart(6, '0');
      var set = function (id, v) { var e = document.getElementById(id); if (e) { e.textContent = v; } };
      set('rp-code', rk.code);
      set('rp-zone', 'Zone ' + rk.zone);
      set('rp-cap', fmt(rk.capacity) + ' palettes');
      set('rp-occ', fmt(rk.occupied) + ' palettes');
      set('rp-free', fmt(rk.free) + ' places');
      set('rp-rate', pct + ' %');
      var g = document.getElementById('rp-gauge');
      if (g) { g.style.width = Math.min(pct, 100) + '%'; g.style.background = color; }
      var rateEl = document.getElementById('rp-rate');
      if (rateEl) { rateEl.style.color = color; }
      var codeEl = document.getElementById('rp-code');
      if (codeEl) { codeEl.style.borderColor = color; }
    }

    // Interactions.
    var ray = new THREE.Raycaster();
    var mouse = new THREE.Vector2();
    var down = null;
    function pick(e) {
      var rect = renderer.domElement.getBoundingClientRect();
      mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
      mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
      ray.setFromCamera(mouse, camera);
      var hit = ray.intersectObject(shells);
      return (hit.length && hit[0].instanceId != null) ? hit[0].instanceId : -1;
    }
    // Événements POINTER (les événements souris de compatibilité sont supprimés
    // par OrbitControls qui appelle preventDefault sur pointerdown).
    renderer.domElement.addEventListener('pointerdown', function (e) { down = { x: e.clientX, y: e.clientY }; });
    renderer.domElement.addEventListener('pointerup', function (e) {
      if (!down) { return; }
      var moved = Math.abs(e.clientX - down.x) + Math.abs(e.clientY - down.y);
      down = null;
      if (moved > 6) { return; } // c'était un drag, pas un clic
      var r = pick(e);
      if (r >= 0) { selectRack(r); }
    });
    renderer.domElement.addEventListener('pointermove', function (e) {
      if (!tip) { return; }
      var r = pick(e);
      if (r >= 0) {
        var rk = pos[r].rk;
        tip.style.display = 'block';
        var rect = renderer.domElement.getBoundingClientRect();
        tip.style.left = (e.clientX - rect.left + 12) + 'px';
        tip.style.top = (e.clientY - rect.top + 12) + 'px';
        tip.textContent = rk.code + ' · ' + Math.round(rk.rate * 100) + '% (' + rk.occupied + '/' + rk.capacity + ')';
        renderer.domElement.style.cursor = 'pointer';
      } else {
        tip.style.display = 'none';
        renderer.domElement.style.cursor = 'grab';
      }
    });

    // Boutons.
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
        defaultCam(); controls.target.set(cx, maxH / 2, cz); controls.update();
      });
    }
    var btnCodes = document.getElementById('btn-codes');
    var codesVisible = true;
    if (btnCodes) {
      btnCodes.addEventListener('click', function () {
        codesVisible = !codesVisible;
        for (var i = 0; i < codeSprites.length; i++) { codeSprites[i].visible = codesVisible; }
        btnCodes.classList.toggle('active', codesVisible);
      });
    }

    // Boucle + resize.
    function animate() { requestAnimationFrame(animate); controls.update(); renderer.render(scene, camera); }
    animate();
    window.addEventListener('resize', function () {
      var W = container.clientWidth, H = container.clientHeight || 620;
      camera.aspect = W / H; camera.updateProjectionMatrix(); renderer.setSize(W, H);
    });
  }
})();
