<!-- Modal Visualização de Triagem (Para Médicos) -->
<div id="modal-triagem-view" class="fixed inset-0 z-[100] hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity" onclick="fecharTriagemView()"></div>
    
    <div class="bg-white rounded-[2rem] w-full max-w-2xl mx-4 relative z-10 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden transform transition-all border border-white">
        <!-- Cabeçalho -->
        <div class="px-8 py-6 border-b border-surface-container-low flex justify-between items-center bg-blue-50/50">
            <div>
                <h3 class="text-2xl font-headline font-extrabold tracking-tight text-blue-900 flex items-center gap-3">
                    <span class="material-symbols-outlined text-blue-600">vital_signs</span>
                    Dados de Triagem
                </h3>
                <p class="text-blue-700/70 font-semibold text-sm mt-1" id="tv-paciente-nome"></p>
            </div>
            <button type="button" onclick="fecharTriagemView()" class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-on-surface-variant hover:bg-blue-100 hover:text-blue-700 transition-colors shadow-sm">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Corpo do Modal -->
        <div class="p-8 overflow-y-auto custom-scrollbar flex-1 bg-surface-container-low/30">
            <!-- Sem triagem alert -->
            <div id="tv-sem-triagem" class="hidden text-center py-10">
                <span class="material-symbols-outlined text-6xl text-surface-container-highest mb-4">assignment_late</span>
                <p class="text-lg font-bold text-on-surface-variant">O paciente ainda não passou pela triagem.</p>
                <p class="text-sm text-on-surface-variant/70 mt-2">Os sinais vitais e os sintomas não foram registados pela recepção/enfermagem.</p>
            </div>

            <div id="tv-com-triagem" class="space-y-6">
                <!-- Sinais Vitais Grid -->
                <div>
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[14px]">monitor_heart</span> Sinais Vitais
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="bg-white p-4 rounded-2xl border border-blue-50 shadow-sm flex flex-col justify-center">
                            <span class="text-[10px] font-black text-on-surface-variant uppercase mb-1">Temp.</span>
                            <div class="flex items-baseline gap-1 text-blue-700">
                                <span class="text-2xl font-extrabold" id="tv-temperatura">—</span>
                                <span class="text-xs font-bold">°C</span>
                            </div>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-blue-50 shadow-sm flex flex-col justify-center">
                            <span class="text-[10px] font-black text-on-surface-variant uppercase mb-1">P. Arterial</span>
                            <div class="flex items-baseline gap-1 text-blue-700">
                                <span class="text-2xl font-extrabold" id="tv-pressao">—</span>
                                <span class="text-xs font-bold">mmHg</span>
                            </div>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-blue-50 shadow-sm flex flex-col justify-center">
                            <span class="text-[10px] font-black text-on-surface-variant uppercase mb-1">Freq. Card.</span>
                            <div class="flex items-baseline gap-1 text-blue-700">
                                <span class="text-2xl font-extrabold" id="tv-freq">—</span>
                                <span class="text-xs font-bold">bpm</span>
                            </div>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-blue-50 shadow-sm flex flex-col justify-center">
                            <span class="text-[10px] font-black text-on-surface-variant uppercase mb-1">Peso</span>
                            <div class="flex items-baseline gap-1 text-blue-700">
                                <span class="text-2xl font-extrabold" id="tv-peso">—</span>
                                <span class="text-xs font-bold">kg</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sintomas -->
                <div class="bg-white p-5 rounded-2xl border border-blue-50 shadow-sm">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[14px]">sick</span> Sintomas Relatados
                    </h4>
                    <p class="text-sm font-medium text-on-surface leading-relaxed whitespace-pre-wrap" id="tv-sintomas">—</p>
                </div>

                <!-- Observações -->
                <div class="bg-white p-5 rounded-2xl border border-blue-50 shadow-sm">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[14px]">edit_note</span> Observações da Triagem
                    </h4>
                    <p class="text-sm font-medium text-on-surface leading-relaxed whitespace-pre-wrap" id="tv-observacoes">—</p>
                </div>
            </div>
        </div>
        
        <div class="p-6 bg-white border-t border-surface-container-low">
            <button type="button" onclick="fecharTriagemView()" class="w-full bg-surface-container-low text-on-surface-variant px-4 py-4 rounded-xl text-sm font-black hover:bg-surface-container transition-colors">
                Fechar Detalhes
            </button>
        </div>
    </div>
</div>

<script>
function abrirTriagemView(pacienteNome, triagemData) {
    document.getElementById('tv-paciente-nome').textContent = pacienteNome;
    
    if (!triagemData || !triagemData.id) {
        document.getElementById('tv-sem-triagem').classList.remove('hidden');
        document.getElementById('tv-com-triagem').classList.add('hidden');
    } else {
        document.getElementById('tv-sem-triagem').classList.add('hidden');
        document.getElementById('tv-com-triagem').classList.remove('hidden');
        
        document.getElementById('tv-temperatura').textContent = triagemData.temperatura || '—';
        document.getElementById('tv-pressao').textContent = triagemData.pressao_arterial || '—';
        document.getElementById('tv-freq').textContent = triagemData.frequencia_cardiaca || '—';
        document.getElementById('tv-peso').textContent = triagemData.peso || '—';
        document.getElementById('tv-sintomas').textContent = triagemData.sintomas || 'Nenhum sintoma descrito.';
        document.getElementById('tv-observacoes').textContent = triagemData.observacoes || 'Nenhuma observação adicionada.';
    }
    
    document.getElementById('modal-triagem-view').classList.remove('hidden');
}

function fecharTriagemView() {
    document.getElementById('modal-triagem-view').classList.add('hidden');
}
</script>
