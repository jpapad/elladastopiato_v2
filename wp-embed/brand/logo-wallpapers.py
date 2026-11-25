import json, math
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.pens.boundsPen import BoundsPen

G=json.load(open('/home/user/elladastopiato_v2/public/greece-regions.geojson'))
# projection
lat0=38.5; k=math.cos(math.radians(lat0))
polys=[]
for f in G['features']:
    crete = f['properties']['name_greek']=='Κρήτη'
    g=f['geometry']; cs=g['coordinates'] if g['type']=='MultiPolygon' else [g['coordinates']]
    for poly in cs:
        ring=poly[0]
        pts=[(lon*k,-lat) for lon,lat in ring]
        a=0
        for i in range(len(pts)):
            x1,y1=pts[i];x2,y2=pts[(i+1)%len(pts)];a+=x1*y2-x2*y1
        a=abs(a)/2
        polys.append((crete,a,pts))
# drop tiny islands (keeps the look of the original emblem)
maxa=max(p[1] for p in polys)
keep=[p for p in polys if p[1]>maxa*0.0012 or (p[0] and p[1]>maxa*0.0005)]
xs=[x for p in keep for x,_ in p[2]]; ys=[y for p in keep for _,y in p[2]]
minx,maxx,miny,maxy=min(xs),max(xs),min(ys),max(ys)
GW=maxx-minx; GH=maxy-miny
def mappath(cx,cy,size):
    s=size/max(GW,GH); ox=cx-GW*s/2; oy=cy-GH*s/2
    white=[];red=[]
    for crete,a,pts in keep:
        d='M'+' L'.join('%.1f,%.1f'%(ox+(x-minx)*s,oy+(y-miny)*s) for x,y in pts)+'Z'
        (red if crete else white).append(d)
    return ''.join(white),''.join(red)

font=TTFont('/home/user/elladastopiato_v2/wp-embed/wordpress-plugin/ellada-map/public/fonts/FiraSansExtraCondensed_800ExtraBold.ttf')
gs=font.getGlyphSet(); cmap=font.getBestCmap(); upm=font['head'].unitsPerEm
hmtx=font['hmtx']
def textpath(txt,x,y,size,track=0):
    sc=size/upm; out=[]; cx=x
    for ch in txt:
        gn=cmap.get(ord(ch))
        if not gn: continue
        pen=SVGPathPen(gs); tp=TransformPen(pen,(sc,0,0,-sc,cx,y)); gs[gn].draw(tp); out.append(pen.getCommands())
        cx+=hmtx[gn][0]*sc+track*size
    return ' '.join(out), cx-x-track*size
def textwidth(txt,size,track=0):
    return textpath(txt,0,0,size,track)[1]
def capheight(size):
    bp=BoundsPen(gs); gs[cmap[ord('Ε')]].draw(bp); return (bp.bounds[3])*size/upm

PET='#174C4B'; DARK='#123C3B'; CREAM='#F6F2E9'; TERRA='#D97745'; GOLD='#D29A48'

def emblem(cx,cy,r,ring=True):
    w,red=mappath(cx,cy+r*0.02,r*1.42)
    s=f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="{DARK}"/>'
    if ring: s+=f'<circle cx="{cx}" cy="{cy}" r="{r*0.9}" fill="none" stroke="#0B2B2A" stroke-width="{r*0.018}" opacity=".9"/><circle cx="{cx}" cy="{cy}" r="{r*0.9}" fill="none" stroke="{TERRA}" stroke-width="{r*0.006}" opacity=".35"/>'
    s+=f'<path d="{w}" fill="#F5F4F2"/><path d="{red}" fill="{TERRA}"/>'
    return s

def lockup_h(x,y,h,top=CREAM,bottom=TERRA):
    """οριζόντιο: σήμα + δύο γραμμές· h = ύψος σήματος· (x,y)=πάνω αριστερά"""
    r=h/2; s=emblem(x+r,y+r,r)
    size=h*0.62; ch=capheight(size); gap=h*0.08
    tx=x+h*1.13; total=ch*2+gap; ty=y+(h-total)/2
    p1,_=textpath('ΕΛΛΑΔΑ',tx,ty+ch,size,0.0); p2,_=textpath('ΣΤΟ ΠΙΑΤΟ',tx,ty+2*ch+gap,size,0.0)
    w=max(textwidth('ΕΛΛΑΔΑ',size),textwidth('ΣΤΟ ΠΙΑΤΟ',size))
    s+=f'<path d="{p1}" fill="{top}"/><path d="{p2}" fill="{bottom}"/>'
    return s, h*1.13+w

def lockup_v(cx,y,h,top=CREAM,bottom=TERRA):
    """κατακόρυφο: σήμα πάνω, κείμενο από κάτω, κεντραρισμένα"""
    r=h/2; s=emblem(cx,y+r,r)
    size=h*0.36; ch=capheight(size); gap=h*0.05; ty=y+h+h*0.16
    w1=textwidth('ΕΛΛΑΔΑ',size); w2=textwidth('ΣΤΟ ΠΙΑΤΟ',size)
    p1,_=textpath('ΕΛΛΑΔΑ',cx-w1/2,ty+ch,size); p2,_=textpath('ΣΤΟ ΠΙΑΤΟ',cx-w2/2,ty+2*ch+gap,size)
    s+=f'<path d="{p1}" fill="{top}"/><path d="{p2}" fill="{bottom}"/>'
    return s, ty+2*ch+gap-y

def bg(W,H,dark=True):
    if dark:
        return (f'<defs><radialGradient id="g" cx="50%" cy="45%" r="75%"><stop offset="0" stop-color="#1D5958"/><stop offset=".55" stop-color="{PET}"/><stop offset="1" stop-color="#0E3534"/></radialGradient></defs>'
                f'<rect width="{W}" height="{H}" fill="url(#g)"/>')
    return (f'<defs><radialGradient id="g" cx="50%" cy="45%" r="75%"><stop offset="0" stop-color="#FAF8F2"/><stop offset=".6" stop-color="{CREAM}"/><stop offset="1" stop-color="#E7E2D5"/></radialGradient></defs>'
            f'<rect width="{W}" height="{H}" fill="url(#g)"/>')

def tagline(cx,y,size,color):
    t='ΑΝΑΚΑΛΥΠΤΟΥΜΕ ΤΗΝ ΕΛΛΑΔΑ ΜΕΣΑ ΑΠΟ ΤΙΣ ΓΕΥΣΕΙΣ'
    w=textwidth(t,size,0.12); p,_=textpath(t,cx-w/2,y,size,0.12)
    return f'<path d="{p}" fill="{color}" opacity=".55"/>'
def rule(cx,y,w,h):
    return f'<rect x="{cx-w/2}" y="{y}" width="{w/2}" height="{h}" fill="{GOLD}"/><rect x="{cx}" y="{y}" width="{w/2}" height="{h}" fill="{TERRA}"/>'

out={}
# 1. Desktop 3840x2160
for dark in (True,False):
    W,H=3840,2160; h=520
    # measure width
    _,lw=lockup_h(0,0,h)
    x=(W-lw)/2; y=(H-h)/2-80
    s,_=lockup_h(x,y,h, CREAM if dark else PET, TERRA)
    s=bg(W,H,dark)+s+rule(W/2,y+h+150,260,8)+tagline(W/2,y+h+260,46,CREAM if dark else PET)
    out[f'desktop-{"petrol" if dark else "krem"}-3840x2160']=(W,H,s)
# 2. Phone 1290x2796
for dark in (True,False):
    W,H=1290,2796; h=560
    s,th=lockup_v(W/2,0,h, CREAM if dark else PET, TERRA)
    y=H*0.53-th/2
    s,th=lockup_v(W/2,y,h, CREAM if dark else PET, TERRA)
    s=bg(W,H,dark)+s+rule(W/2,y+th+110,200,7)
    out[f'kinito-{"petrol" if dark else "krem"}-1290x2796']=(W,H,s)
# 3. Λογότυπο μόνο (διαφανές), οριζόντιο και σήμα
_,lw=lockup_h(0,0,1000)
s,_=lockup_h(20,20,1000); out['logotypo-orizontio-diafanes']=(int(lw+40),1040,s)
s=emblem(1024,1024,1000); out['sima-diafanes-2048']=(2048,2048,s)
for k,(W,H,s) in out.items():
    open(f'out/{k}.svg','w').write(f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}">{s}</svg>')
print(list(out), len(keep), 'polys kept of', len(polys))
