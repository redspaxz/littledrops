/**
 * PBMS charts — dependency-free SVG/CSS charts in vanilla JavaScript.
 * Self-initialises: any element with [data-chart] gets rendered from
 * [data-chart-data] (JSON). Keeps the app fully offline (no CDN).
 *
 * Supported:
 *   data-chart="line"  { labels: [...], series: [{name, color, dashed, area, values: [...]}] }
 *   data-chart="donut" { data: [{label, value, color}], center: {value, label} }
 *   data-chart="bars"  { data: [{label, value, color, title}] }
 */
(function () {
  'use strict';

  var SVG_NS = 'http://www.w3.org/2000/svg';

  function svgEl(tag, attrs) {
    var el = document.createElementNS(SVG_NS, tag);
    for (var k in attrs) { el.setAttribute(k, attrs[k]); }
    return el;
  }

  /** 1250000 -> "1,3M" style short money (XAF has no minor units). */
  function fmtShort(n) {
    n = Math.round(n);
    if (Math.abs(n) >= 1e6) { return (n / 1e6).toFixed(1).replace('.', ',') + 'M'; }
    if (Math.abs(n) >= 1e3) { return Math.round(n / 1e3) + 'k'; }
    return String(n);
  }

  function fmtFull(n) {
    return Number(n).toLocaleString('fr-FR') + ' FCFA';
  }

  function legend(colors) {
    var wrap = document.createElement('div');
    wrap.className = 'chart-legend';
    colors.forEach(function (c) {
      var chip = document.createElement('span');
      chip.className = 'chart-chip';
      var dot = document.createElement('i');
      dot.style.background = c.color || '#8a9aa6';
      chip.appendChild(dot);
      chip.appendChild(document.createTextNode(c.name));
      wrap.appendChild(chip);
    });
    return wrap;
  }

  /* ---------------- line / area chart (SVG) ---------------- */
  function renderLine(host, cfg) {
    var W = 640, H = 240, L = 56, R = 16, T = 16, B = 32;
    var plotW = W - L - R, plotH = H - T - B;
    var labels = cfg.labels || [];
    var n = labels.length;
    var all = [];
    (cfg.series || []).forEach(function (s) { all = all.concat(s.values || []); });
    var rawMax = Math.max.apply(null, all.concat([1]));
    // "nice" maximum on a 1-2-2.5-5 ladder so gridlines land on round numbers
    var exp = Math.pow(10, Math.floor(Math.log(rawMax) / Math.LN10));
    var frac = rawMax / exp;
    var nice = [1, 1.2, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10];
    for (var i = 0; i < nice.length; i++) { if (nice[i] >= frac) { frac = nice[i]; break; } }
    var maxV = frac * exp;

    function x(i) { return n <= 1 ? L + plotW / 2 : L + plotW * i / (n - 1); }
    function y(v) { return T + plotH * (1 - v / maxV); }

    var svg = svgEl('svg', { viewBox: '0 0 ' + W + ' ' + H, 'class': 'chart-svg', role: 'img' });

    // horizontal gridlines + y labels
    for (var g = 0; g <= 4; g++) {
      var gy = T + plotH * g / 4;
      var gv = maxV * (4 - g) / 4;
      svg.appendChild(svgEl('line', {
        x1: L, x2: W - R, y1: gy, y2: gy,
        stroke: g === 4 ? '#c8d2da' : '#e4eaef', 'stroke-width': g === 4 ? 1.2 : 1
      }));
      var yt = svgEl('text', { x: L - 8, y: gy + 4, 'text-anchor': 'end', 'class': 'chart-tick' });
      yt.textContent = fmtShort(gv);
      svg.appendChild(yt);
    }

    // x labels
    labels.forEach(function (lb, i2) {
      var t = svgEl('text', { x: x(i2), y: H - 8, 'text-anchor': 'middle', 'class': 'chart-tick' });
      t.textContent = lb;
      svg.appendChild(t);
    });

    (cfg.series || []).forEach(function (s) {
      var vals = s.values || [];
      var pts = vals.map(function (v, i2) { return x(i2) + ',' + y(v); });

      if (s.area) {
        var path = svgEl('path', {
          d: 'M' + x(0) + ',' + (T + plotH) + ' L' + pts.join(' L') +
             ' L' + x(vals.length - 1) + ',' + (T + plotH) + ' Z',
          fill: s.color, opacity: 0.12
        });
        svg.appendChild(path);
      }
      svg.appendChild(svgEl('polyline', {
        points: pts.join(' '), fill: 'none', stroke: s.color,
        'stroke-width': 2.2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round',
        'stroke-dasharray': s.dashed ? '6 5' : 'none'
      }));
      vals.forEach(function (v, i2) {
        var dot = svgEl('circle', {
          cx: x(i2), cy: y(v), r: 3.4, fill: '#fff', stroke: s.color, 'stroke-width': 2
        });
        var title = svgEl('title', {});
        title.textContent = (s.name ? s.name + ' — ' : '') + labels[i2] + ': ' + fmtFull(v);
        dot.appendChild(title);
        svg.appendChild(dot);
      });
    });

    host.appendChild(svg);
    host.appendChild(legend((cfg.series || []).map(function (s) { return { name: s.name, color: s.color }; })));
  }

  /* ---------------- donut chart (SVG) ---------------- */
  function renderDonut(host, cfg) {
    var data = cfg.data || [];
    var total = data.reduce(function (a, d) { return a + d.value; }, 0);
    var svg = svgEl('svg', {
      viewBox: '0 0 200 200', 'class': 'chart-svg chart-donut', role: 'img'
    });
    var r = 62, cx = 100, cy = 100, C = 2 * Math.PI * r;
    var group = svgEl('g', { transform: 'rotate(-90 100 100)' });
    var acc = 0;
    data.forEach(function (d) {
      if (!d.value) { return; }
      var seg = svgEl('circle', {
        cx: cx, cy: cy, r: r, fill: 'none',
        stroke: d.color, 'stroke-width': 26,
        'stroke-dasharray': (d.value / total * C) + ' ' + (C - d.value / total * C),
        'stroke-dashoffset': -acc / total * C
      });
      var title = svgEl('title', {});
      title.textContent = d.label + ': ' + d.value + ' (' + Math.round(d.value / total * 100) + '%)';
      seg.appendChild(title);
      group.appendChild(seg);
      acc += d.value;
    });
    svg.appendChild(group);

    if (cfg.center) {
      var v = svgEl('text', { x: cx, y: cy - 2, 'text-anchor': 'middle', 'class': 'chart-center-value' });
      v.textContent = cfg.center.value;
      var l = svgEl('text', { x: cx, y: cy + 18, 'text-anchor': 'middle', 'class': 'chart-center-label' });
      l.textContent = cfg.center.label;
      svg.appendChild(v);
      svg.appendChild(l);
    }
    host.appendChild(svg);
    host.appendChild(legend(data.map(function (d) {
      return { name: d.label + ' · ' + d.value, color: d.color };
    })));
  }

  /* ---------------- horizontal bars (CSS) ---------------- */
  function renderBars(host, cfg) {
    var data = cfg.data || [];
    var max = Math.max.apply(null, data.map(function (d) { return d.value; }).concat([1]));
    var wrap = document.createElement('div');
    wrap.className = 'chart-bars';
    data.forEach(function (d) {
      var row = document.createElement('div');
      row.className = 'chart-bar-row';
      var label = document.createElement('span');
      label.className = 'chart-bar-label';
      label.textContent = d.label;
      var track = document.createElement('div');
      track.className = 'chart-bar-track';
      var fill = document.createElement('div');
      fill.className = 'chart-bar-fill';
      fill.style.width = (d.value / max * 100) + '%';
      fill.style.background = d.color || '#0e6e5c';
      fill.title = d.title || (d.label + ': ' + fmtFull(d.value));
      track.appendChild(fill);
      var value = document.createElement('span');
      value.className = 'chart-bar-value';
      value.textContent = fmtShort(d.value);
      row.appendChild(label);
      row.appendChild(track);
      row.appendChild(value);
      row.title = fill.title;
      wrap.appendChild(row);
    });
    host.appendChild(wrap);
  }

  var RENDERERS = { line: renderLine, donut: renderDonut, bars: renderBars };

  function init() {
    document.querySelectorAll('[data-chart]').forEach(function (host) {
      var type = host.getAttribute('data-chart');
      var raw = host.getAttribute('data-chart-data');
      if (!RENDERERS[type] || !raw) { return; }
      var cfg;
      try { cfg = JSON.parse(raw); } catch (e) { return; }
      try { RENDERERS[type](host, cfg); } catch (e) { /* leave panel empty */ }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.PBMSCharts = { line: renderLine, donut: renderDonut, bars: renderBars, fmtShort: fmtShort };
})();
