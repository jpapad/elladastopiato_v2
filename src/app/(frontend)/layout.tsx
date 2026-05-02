import React from 'react'
import './globals.css'
import { Inter } from 'next/font/google'
import Navbar from '@/components/Navbar' 
import { Footer } from '@/components/Footer'
import { PageWrapper } from '@/components/PageWrapper'

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
    <html lang="el" className="scroll-smooth" data-scroll-behavior="smooth">
      <body className={`${inter.className} bg-[#050505] text-white antialiased selection:bg-orange-500 selection:text-black`}>
        
        <Navbar /> 
        
        <div className="flex flex-col min-h-screen">
          {/* Το pt-28 διασφαλίζει ότι το περιεχόμενο δεν κρύβεται πίσω από το fixed navbar */}
          <main className="flex-grow pt-28"> 
            <PageWrapper>
              {children}
            </PageWrapper>
          </main>
          
          <Footer />
        </div>

      </body>
    </html>
  )
}