const {chromium}=require('playwright');(async()=>{const b=await chromium.launch({args:['--disable-gpu']});
for(const [f,w,h,o] of [['profile.html',1080,1080,'ellada-profil-1080.png'],['cover.html',1640,624,'ellada-facebook-cover-1640x624.png']]){const p=await b.newPage({viewport:{width:w,height:h},deviceScaleFactor:1});await p.goto('file://'+__dirname+'/'+f);await p.waitForTimeout(800);await p.screenshot({path:o});await p.close()}
await b.close()})();
