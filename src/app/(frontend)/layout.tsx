import React from 'react'
import './globals.css'
import { Inter } from 'next/font/google'
import { cookies } from 'next/headers'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import Navbar from '@/components/Navbar'
import { Footer } from '@/components/Footer'
import { PageWrapper } from '@/components/PageWrapper'
import { ThemeProvider } from '@/components/ThemeProvider'
import MaintenancePage from '@/components/MaintenancePage'

export const dynamic = 'force-dynamic'

const inter = Inter({
  subsets: ['latin', 'greek'],
  display: 'swap',
})

export const metadata = {
  title: {
    template: '%s | Ελλάδα στο Πιάτο',
    default: 'Ελλάδα στο Πιάτο | Αυθεντικές Ελληνικές Συνταγές',
  },
  description: 'Ελλάδα στο Πιάτο - Ανακαλύψτε την παραδοσιακή ελληνική κουζίνα μέσα από έναν διαδραστικό γαστρονομικό χάρτη.',
}

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  // Maintenance mode check
  let maintenance = false
  let maintenanceData: any = {}
  try {
    const payload = await getPayload({ config: configPromise })
    const settings = await payload.findGlobal({ slug: 'site-settings' }) as any
    if (settings?.maintenanceMode) {
      const cookieStore = await cookies()
      const hasAdminSession = cookieStore.has('payload-token')
      if (!hasAdminSession) {
        maintenance = true
        maintenanceData = settings
      }
    }
  } catch {}

  if (maintenance) {
    return (
      <html lang="el" suppressHydrationWarning>
        <body>
          <MaintenancePage
            title={maintenanceData.maintenanceTitle}
            message={maintenanceData.maintenanceMessage}
            estimate={maintenanceData.maintenanceEstimate}
          />
        </body>
      </html>
    )
  }

  return (
    <html lang="el" className="scroll-smooth" suppressHydrationWarning>
      <body className={`${inter.className} bg-white dark:bg-[#050505] text-gray-900 dark:text-white antialiased selection:bg-orange-500 selection:text-black`}>
        <ThemeProvider>
          <Navbar />
          <div className="flex flex-col min-h-screen">
            <main className="flex-grow pt-28">
              <PageWrapper>
                {children}
              </PageWrapper>
            </main>
            <Footer />
          </div>
        </ThemeProvider>
      </body>
    </html>
  )
}
