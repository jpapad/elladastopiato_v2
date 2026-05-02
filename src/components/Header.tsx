import React from 'react'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { HeaderClient } from './HeaderClient' // Πρέπει να είναι μέσα σε { }

export const Header = async () => {
  try {
    const payload = await getPayload({ config: configPromise })
    
    const menuData: any = await (payload as any).findGlobal({
      slug: 'header-menu',
      next: { revalidate: 30 }, 
    })

    const hasNavLinks = menuData?.navLinks && Array.isArray(menuData.navLinks) && menuData.navLinks.length > 0

    const navLinks = hasNavLinks 
      ? menuData.navLinks 
      : [
          { label: 'Αρχικη', link: '/' },
          { label: 'Περιοχες', link: '/#explore' },
          { label: 'Περι', link: '/about' },
        ]

    return <HeaderClient navLinks={navLinks} />
  } catch (error) {
    console.error("Payload Header Error:", error)
    
    const fallbackLinks = [
      { label: 'Αρχικη', link: '/' },
      { label: 'Περιοχες', link: '/#explore' },
      { label: 'Περι', link: '/about' },
    ]
    
    return <HeaderClient navLinks={fallbackLinks} />
  }
}