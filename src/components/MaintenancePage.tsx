import { Wrench, Clock } from 'lucide-react'

interface Props {
  title?: string
  message?: string
  estimate?: string
}

export default function MaintenancePage({
  title = 'Υπό Ανανέωση',
  message = 'Κάνουμε κάποιες βελτιώσεις. Σύντομα θα είμαστε πάλι εδώ!',
  estimate = 'Επιστρέφουμε σύντομα',
}: Props) {
  return (
    <div className="min-h-screen bg-[#050505] flex items-center justify-center px-6">
      <div className="text-center max-w-2xl mx-auto">
        <div className="flex justify-center mb-10">
          <div className="w-24 h-24 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center">
            <Wrench size={40} className="text-orange-500 animate-pulse" />
          </div>
        </div>

        <span className="text-orange-500 text-[10px] font-black uppercase tracking-[0.5em] mb-6 block">
          Ελλάδα στο Πιάτο
        </span>

        <h1 className="text-6xl md:text-8xl font-black uppercase italic tracking-tighter leading-none text-white mb-8">
          {title}
        </h1>

        <p className="text-white/40 text-lg leading-relaxed mb-12 max-w-md mx-auto">
          {message}
        </p>

        <div className="inline-flex items-center gap-3 bg-white/5 border border-white/10 px-8 py-4 rounded-full">
          <Clock size={16} className="text-orange-500" />
          <span className="text-white/60 text-sm font-medium">{estimate}</span>
        </div>
      </div>
    </div>
  )
}
