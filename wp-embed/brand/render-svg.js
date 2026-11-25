const { chromium } = require('playwright');const fs=require('fs');const path=require('path');
(async()=>{const b=await chromium.launch({args:['--disable-gpu','--force-device-scale-factor=1']});const dir=path.resolve(process.argv[2]);
for(const f of fs.readdirSync(dir).filter(f=>f.endsWith('.svg'))){const svg=fs.readFileSync(path.join(dir,f),'utf8');const m=svg.match(/width="(\d+)" height="(\d+)"/);const W=+m[1],H=+m[2];
const p=await b.newPage({viewport:{width:W,height:H},deviceScaleFactor:1});await p.goto('file://'+path.join(dir,f));await p.waitForTimeout(300);
try{await p.screenshot({path:path.join(dir,f.replace('.svg','.png')),omitBackground:true});console.log('ok',f);}catch(e){console.log('FAIL',f,String(e).slice(0,80));}await p.close();}
await b.close();})();
