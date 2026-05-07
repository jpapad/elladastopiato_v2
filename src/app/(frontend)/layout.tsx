import React from 'react'
import './globals.css'
import { Inter, Fraunces } from 'next/font/google'
import { cookies } from 'next/headers'
import { getPayload } from 'payload'
import configPromise from '@payload-config'
import { unstable_cache } from 'next/cache'
import Navbar from '@/components/Navbar'
import { Footer } from '@/components/Footer'
import { PageWrapper } from '@/components/PageWrapper'
import { ThemeProvider } from '@/components/ThemeProvider'
import MaintenancePage from '@/components/MaintenancePage'

export const dynamic = 'force-dynamic'

const getMaintenanceSettings = unstable_cache(
  async () => {
    try {
      const payload = await getPayload({ config: configPromise })
      const s = await payload.findGlobal({ slug: 'site-settings', depth: 1 }) as any
      return {
        maintenanceMode: s?.maintenanceMode ?? false,
        title: s?.maintenanceTitle,
        message: s?.maintenanceMessage,
        estimate: s?.maintenanceEstimate,
        logo: s?.logo?.url ? { url: s.logo.url as string, alt: (s.logo.alt as string) || '' } : null,
        logoHeight: s?.logoHeight ? parseInt(s.logoHeight as string) : 40,
      }
    } catch {
      return { maintenanceMode: false, logo: null, logoHeight: 40 }
    }
  },
  ['maintenance-settings'],
  { revalidate: 30 }
)

const inter = Inter({
  subsets: ['latin', 'greek'],
  display: 'swap',
})

const fraunces = Fraunces({
  subsets: ['latin'],
  display: 'swap',
  variable: '--font-fraunces',
  weight: ['300', '400', '500', '600', '700', '800', '900'],
  style: ['normal', 'italic'],
})

export const metadata = {
  title: {
    template: '%s | Ελλάδα στο Πιάτο',
    default: 'Ελλάδα στο Πιάτο | Αυθεντικές Ελληνικές Συνταγές',
  },
  description: 'Ελλάδα στο Πιάτο - Ανακαλύψτε την παραδοσιακή ελληνική κουζίνα μέσα από έναν διαδραστικό γαστρονομικό χάρτη.',
}

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  // Maintenance mode check (cached 30s)
  let maintenance = false
  let maintenanceData: any = {}
  const settings = await getMaintenanceSettings()
  if (settings.maintenanceMode) {
    const cookieStore = await cookies()
    const hasAdminSession = cookieStore.has('payload-token')
    if (!hasAdminSession) {
      maintenance = true
      maintenanceData = settings
    }
  }

  if (maintenance) {
    return (
      <html lang="el" suppressHydrationWarning>
        <body>
          <MaintenancePage
            title={maintenanceData.title}
            message={maintenanceData.message}
            estimate={maintenanceData.estimate}
          />
        </body>
      </html>
    )
  }

  return (
    <html lang="el" className="scroll-smooth" suppressHydrationWarning>
      <body className={`${inter.className} ${fraunces.variable} bg-[#FAF8F5] dark:bg-[#0E0C0A] text-gray-900 dark:text-white antialiased selection:bg-orange-500 selection:text-black`}>
        <ThemeProvider>
          <Navbar logo={settings.logo ?? null} logoHeight={settings.logoHeight ?? 40} />
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
