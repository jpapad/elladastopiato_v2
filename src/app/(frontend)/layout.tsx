import React from 'react'
import './globals.css'
import { Inter } from 'next/font/google'
import Navbar from '@/components/Navbar'
import { Footer } from '@/components/Footer'
import { PageWrapper } from '@/components/PageWrapper'
import { ThemeProvider } from '@/components/ThemeProvider'

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

export default function RootLayout({ children }: { children: React.ReactNode }) {
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
