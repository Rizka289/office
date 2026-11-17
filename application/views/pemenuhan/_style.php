<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
  .pm { --brand:#2f7a57; --brand-dark:#1f5a3f; --line:#e9edeb; }
  .pm .hero { background:linear-gradient(135deg,var(--brand),var(--brand-dark)); color:#fff; border-radius:14px; padding:18px 24px; }
  .pm .hero .judul { font-size:1.35rem; font-weight:700; }
  .pm .hero .meta { opacity:.88; font-size:.9rem; }
  .pm .card { border:0; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.08); }
  .pm .card-header { background:#fff; border-bottom:1px solid var(--line); font-weight:600; }
  .pm .table th { font-weight:500; color:#5d6862; white-space:nowrap; }
  .pm .langkah { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:10px; }
  .pm .langkah div { background:#f6f8f7; border-radius:10px; padding:10px 12px; font-size:.86rem; }
  .pm .langkah b { color:var(--brand-dark); display:block; }
  .pm .muted { color:#7b857f; font-size:.82rem; }
  .pm .note { background:#f6f8f7; border-radius:8px; padding:8px 12px; font-size:.86rem; }
  .pm .mini-bar { height:5px; border-radius:3px; background:#e6ebe8; overflow:hidden; margin-top:3px; }
  .pm .mini-bar > div { height:100%; background:var(--brand); }
  .pm form.d-inline { margin:0; }
</style>
