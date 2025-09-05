<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário de Agendamentos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
        .tooltip {
            position: absolute;
            z-index: 1000;
            padding: 12px 16px;
            color: white;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-left: 4px solid;
        }
        .tooltip.show {
            opacity: 1;
        }
        .tooltip.status-agendada {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-left-color: #92400e;
            animation: none !important;
        }
        .tooltip.status-em_andamento {
            background: linear-gradient(135deg, #10b981, #059669);
            border-left-color: #047857;
            animation: none !important;
        }
        .tooltip.status-concluida {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-left-color: #991b1b;
            animation: none !important;
        }
        .status-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin: 2px;
            cursor: pointer;
        }
        .status-agendada { background-color: #f59e0b; }
        .status-em-andamento { 
            background-color: #10b981;
            animation: pulse 2s infinite;
        }
        .status-em_andamento { 
            background-color: #10b981;
            animation: pulse 2s infinite;
        }
        .status-concluida { background-color: #ef4444; }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.7;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="bg-gray-50 h-screen overflow-hidden" x-data="calendarApp()">
    <!-- Tooltip -->
    <div id="tooltip" class="tooltip"></div>

    <!-- Header -->
    <div class="bg-white shadow-sm border-b px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-8">
                <h1 class="text-2xl font-bold text-gray-900">Calendário de Agendamentos</h1>
                
                <!-- Status Guide -->
                <div class="flex items-center space-x-4 text-sm">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span class="text-gray-600">Em andamento</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span class="text-gray-600">Concluída</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                        <span class="text-gray-600">Agendada</span>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex space-x-3">
                <button @click="openMeetingModal()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Agendar Reunião
                </button>
                <button @click="openReportsModal()" 
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Relatórios
                </button>
            </div>
        </div>
    </div>

    <!-- Calendar -->
    <div class="flex-1 p-6">
        <div class="bg-white rounded-lg shadow-sm h-full">
            <!-- Calendar Header -->
            <div class="flex items-center justify-between p-6 border-b">
                <button @click="previousMonth()" class="p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                
                <h2 class="text-xl font-semibold text-gray-900" x-text="currentMonthName + ' ' + currentYear"></h2>
                
                <button @click="nextMonth()" class="p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Calendar Grid -->
            <div class="p-6">
                <!-- Calendar Days -->
                <div class="grid grid-cols-7 gap-2" style="height: calc(100vh - 220px);">
                    <template x-for="day in calendarDays" :key="day.date">
                        <div class="border border-gray-200 p-3 cursor-pointer hover:bg-gray-50 flex flex-col rounded-lg"
                             :class="{'bg-blue-50 border-blue-300': day.isToday}"
                             @click="openMeetingModal(day.date)">
                            <span class="text-lg font-semibold mb-2" x-text="day.day"></span>
                            <div class="flex flex-wrap">
                                <template x-for="meeting in day.meetings" :key="meeting.id">
                                    <div class="status-dot"
                                         :class="'status-' + (meeting.status ? meeting.status.toLowerCase().replace(' ', '_') : 'concluida')"
                                         @click.stop="openMeetingModal(null, meeting.id)"
                                         @mouseenter="showTooltip($event, meeting)"
                                         @mouseleave="hideTooltip()">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Meeting Modal -->
    <div x-show="showMeetingModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4" @click.away="closeMeetingModal()">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4" x-text="meetingForm.id ? 'Editar Reunião' : 'Agendar Reunião'"></h3>
                
                <form @submit.prevent="saveMeeting()">
                    <!-- Participants -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Participantes</label>
                        <select multiple x-model="meetingForm.participants" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                            <template x-for="user in users" :key="user.id">
                                <option :value="user.id" x-text="user.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data da Reunião</label>
                        <input type="date" x-model="meetingForm.data_reuniao" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>

                    <!-- Start Time -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hora de Início</label>
                        <input type="time" x-model="meetingForm.hora_inicio" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>

                    <!-- End Time -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hora de Fim</label>
                        <input type="time" x-model="meetingForm.hora_fim" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>

                    <!-- Subject -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Assunto</label>
                        <input type="text" x-model="meetingForm.assunto" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                        <textarea x-model="meetingForm.descricao" rows="3"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  required></textarea>
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select x-model="meetingForm.status" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                            <option value="agendada">Agendada</option>
                            <option value="em_andamento">Em andamento</option>
                            <option value="concluida">Concluída</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="flex space-x-3">
                        <button type="button" @click="closeMeetingModal()" 
                                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded-lg font-medium transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                            <span x-text="meetingForm.id ? 'Salvar Alterações' : 'Agendar'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reports Modal -->
    <div x-show="showReportsModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 h-5/6" @click.away="closeReportsModal()">
            <div class="p-6 h-full flex flex-col">
                <h3 class="text-lg font-semibold mb-4">Relatórios de Reuniões</h3>
                
                <!-- Filters -->
                <div class="grid grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select x-model="reportFilters.status" @change="loadReports()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos</option>
                            <option value="agendada">Agendada</option>
                            <option value="em_andamento">Em andamento</option>
                            <option value="concluida">Concluída</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                        <input type="date" x-model="reportFilters.date" @change="loadReports()"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Participante</label>
                        <select x-model="reportFilters.participant" @change="loadReports()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos</option>
                            <template x-for="user in users" :key="user.id">
                                <option :value="user.id" x-text="user.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button @click="clearFilters()" 
                                class="w-full bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                            Limpar Filtros
                        </button>
                    </div>
                </div>

                <!-- Reports Table -->
                <div class="flex-1 overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Data</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Horário</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Assunto</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Descrição</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Participantes</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Status</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-700">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="meeting in reportMeetings" :key="meeting.id">
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3" x-text="formatDate(meeting.data_reuniao)"></td>
                                    <td class="px-4 py-3" x-text="meeting.hora_inicio + ' - ' + meeting.hora_fim"></td>
                                    <td class="px-4 py-3" x-text="meeting.assunto"></td>
                                    <td class="px-4 py-3" x-text="meeting.descricao"></td>
                                    <td class="px-4 py-3" x-text="meeting.participantes"></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium"
                                              :class="{
                                                  'bg-yellow-100 text-yellow-800': meeting.status === 'agendada',
                                                  'bg-green-100 text-green-800': meeting.status === 'em_andamento',
                                                  'bg-red-100 text-red-800': meeting.status === 'concluida'
                                              }"
                                              x-text="meeting.status">
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="openMeetingModal(null, meeting.id)" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Close Button -->
                <div class="mt-4 flex justify-end">
                    <button @click="closeReportsModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded-lg font-medium transition-colors">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function calendarApp() {
            return {
                currentDate: new Date(),
                currentMonth: new Date().getMonth(),
                currentYear: new Date().getFullYear(),
                currentMonthName: '',
                daysOfWeek: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
                calendarDays: [],
                meetings: [],
                users: [],
                showMeetingModal: false,
                showReportsModal: false,
                meetingForm: {
                    id: null,
                    participants: [],
                    data_reuniao: '',
                    hora_inicio: '',
                    hora_fim: '',
                    assunto: '',
                    descricao: '',
                    status: 'agendada'
                },
                reportFilters: {
                    status: '',
                    date: '',
                    participant: ''
                },
                reportMeetings: [],

                init() {
                    this.updateCalendar();
                    this.loadUsers();
                    this.loadMeetings();
                    
                    // Auto-refresh every 5 seconds to update meeting status
                    setInterval(() => {
                        this.loadMeetings();
                    }, 5000); // 5 seconds
                },

                updateCalendar() {
                    const monthNames = [
                        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
                    ];
                    
                    this.currentMonthName = monthNames[this.currentMonth];
                    
                    const firstDay = new Date(this.currentYear, this.currentMonth, 1);
                    const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
                    const daysInMonth = lastDay.getDate();
                    
                    this.calendarDays = [];
                    const today = new Date();
                    
                    // Only show days from the current month
                    for (let day = 1; day <= daysInMonth; day++) {
                        const date = new Date(this.currentYear, this.currentMonth, day);
                        
                        const dayMeetings = this.meetings.filter(meeting => {
                            // Fix date comparison - use formatted date string instead of Date object
                            const meetingDateStr = meeting.data_reuniao_formatted || meeting.data_reuniao;
                            const calendarDateStr = date.toISOString().split('T')[0];
                            return meetingDateStr === calendarDateStr;
                        }).sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));
                        
                        this.calendarDays.push({
                            date: date.toISOString().split('T')[0],
                            day: day,
                            isCurrentMonth: true,
                            isToday: date.toDateString() === today.toDateString(),
                            meetings: dayMeetings
                        });
                    }
                },

                previousMonth() {
                    if (this.currentMonth === 0) {
                        this.currentMonth = 11;
                        this.currentYear--;
                    } else {
                        this.currentMonth--;
                    }
                    this.updateCalendar();
                    this.loadMeetings();
                },

                nextMonth() {
                    if (this.currentMonth === 11) {
                        this.currentMonth = 0;
                        this.currentYear++;
                    } else {
                        this.currentMonth++;
                    }
                    this.updateCalendar();
                    this.loadMeetings();
                },

                async loadUsers() {
                    try {
                        const response = await fetch('api/meetings.php?action=users');
                        this.users = await response.json();
                    } catch (error) {
                        console.error('Error loading users:', error);
                    }
                },

                async loadMeetings() {
                    try {
                        const response = await fetch(`api/meetings.php?action=meetings&month=${this.currentMonth + 1}&year=${this.currentYear}`);
                        this.meetings = await response.json();
                        this.updateCalendar();
                    } catch (error) {
                        console.error('Error loading meetings:', error);
                    }
                },

                async loadReports() {
                    try {
                        let url = 'api/meetings.php?action=meetings';
                        const params = new URLSearchParams();
                        
                        if (this.reportFilters.status) params.append('status', this.reportFilters.status);
                        if (this.reportFilters.date) params.append('date', this.reportFilters.date);
                        if (this.reportFilters.participant) params.append('participant', this.reportFilters.participant);
                        
                        if (params.toString()) {
                            url += '&' + params.toString();
                        }
                        
                        const response = await fetch(url);
                        this.reportMeetings = await response.json();
                    } catch (error) {
                        console.error('Error loading reports:', error);
                    }
                },

                openMeetingModal(date = null, meetingId = null) {
                    this.resetMeetingForm();
                    
                    if (date) {
                        this.meetingForm.data_reuniao = date;
                    }
                    
                    if (meetingId) {
                        this.loadMeeting(meetingId);
                    }
                    
                    this.showMeetingModal = true;
                },

                closeMeetingModal() {
                    this.showMeetingModal = false;
                    this.resetMeetingForm();
                },

                openReportsModal() {
                    this.showReportsModal = true;
                    this.loadReports();
                },

                closeReportsModal() {
                    this.showReportsModal = false;
                },

                resetMeetingForm() {
                    this.meetingForm = {
                        id: null,
                        participants: [],
                        data_reuniao: '',
                        hora_inicio: '',
                        hora_fim: '',
                        assunto: '',
                        descricao: '',
                        status: 'agendada'
                    };
                },

                async loadMeeting(id) {
                    try {
                        const response = await fetch(`api/meetings.php?action=meeting&id=${id}`);
                        const meeting = await response.json();
                        
                        this.meetingForm = {
                            id: meeting.id,
                            participants: meeting.participants || [],
                            data_reuniao: meeting.data_reuniao,
                            hora_inicio: meeting.hora_inicio,
                            hora_fim: meeting.hora_fim,
                            assunto: meeting.assunto,
                            descricao: meeting.descricao,
                            status: meeting.status
                        };
                    } catch (error) {
                        console.error('Error loading meeting:', error);
                    }
                },

                async saveMeeting() {
                    try {
                        // Validate date is at least 1 minute in the future
                        const meetingDate = new Date(this.meetingForm.data_reuniao + 'T' + this.meetingForm.hora_inicio);
                        const now = new Date();
                        const oneMinuteFromNow = new Date(now.getTime() + 60000); // Add 1 minute
                        
                        if (meetingDate < oneMinuteFromNow && !this.meetingForm.id) {
                            alert('Reuniões devem ser agendadas com pelo menos 1 minuto de antecedência');
                            return;
                        }
                        
                        const method = this.meetingForm.id ? 'PUT' : 'POST';
                        const response = await fetch('api/meetings.php', {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(this.meetingForm)
                        });
                        
                        const result = await response.json();
                        
                        if (response.ok) {
                            this.closeMeetingModal();
                            this.loadMeetings();
                            if (this.showReportsModal) {
                                this.loadReports();
                            }
                        } else {
                            alert(result.error || 'Erro ao salvar reunião');
                        }
                    } catch (error) {
                        console.error('Error saving meeting:', error);
                        alert('Erro ao salvar reunião');
                    }
                },

                clearFilters() {
                    this.reportFilters = {
                        status: '',
                        date: '',
                        participant: ''
                    };
                    this.loadReports();
                },

                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('pt-BR');
                },

                showTooltip(event, meeting) {
                    const tooltip = document.getElementById('tooltip');
                    
                    // Remove existing status classes
                    tooltip.classList.remove('status-agendada', 'status-em_andamento', 'status-concluida');
                    
                    // Add appropriate status class
                    const statusClass = `status-${meeting.status || 'concluida'}`;
                    tooltip.classList.add(statusClass);
                    
                    // Create status badge
                    const statusText = {
                        'agendada': 'Agendada',
                        'em_andamento': 'Em andamento', 
                        'concluida': 'Concluída'
                    }[meeting.status] || 'Concluída';
                    
                    tooltip.innerHTML = `
                        <div style="margin-bottom: 6px; font-weight: 600;">${meeting.assunto}</div>
                        <div style="margin-bottom: 4px; opacity: 0.9;">🕐 ${meeting.hora_inicio} - ${meeting.hora_fim}</div>
                        <div style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 12px; font-size: 11px; display: inline-block;">${statusText}</div>
                    `;
                    
                    const rect = event.target.getBoundingClientRect();
                    tooltip.style.left = rect.left + 'px';
                    tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
                    tooltip.classList.add('show');
                },

                hideTooltip() {
                    const tooltip = document.getElementById('tooltip');
                    tooltip.classList.remove('show');
                }
            }
        }
    </script>
</body>
</html>
