window.HospitalCalendar = {
    instances: {},
    monthsPT: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
    
    init: function(id, initialDate, minDate) {
        let dateObj = initialDate ? new Date(initialDate + "T00:00:00") : new Date();
        this.instances[id] = {
            currentMonth: dateObj.getMonth(),
            currentYear: dateObj.getFullYear(),
            selectedDate: initialDate,
            minDate: minDate ? new Date(minDate + "T00:00:00").setHours(0,0,0,0) : null
        };
        this.render(id);
    },

    changeMonth: function(id, dir, event) {
        if(event) { event.preventDefault(); event.stopPropagation(); }
        let state = this.instances[id];
        state.currentMonth += dir;
        if(state.currentMonth > 11) { state.currentMonth = 0; state.currentYear++; }
        else if(state.currentMonth < 0) { state.currentMonth = 11; state.currentYear--; }
        this.render(id);
    },

    selectDate: function(id, dateStr, formattedDate, event) {
        if(event) { event.preventDefault(); event.stopPropagation(); }
        this.instances[id].selectedDate = dateStr;
        
        let input = document.getElementById(id + '-input');
        if(input) input.value = dateStr;
        
        let textSpan = document.getElementById(id + '-text');
        if(textSpan) textSpan.textContent = formattedDate;
        
        this.render(id);

        let dropdown = document.getElementById(id + '-dropdown');
        if(dropdown) {
            dropdown.classList.remove('active');
            let w = document.getElementById(id + '-wrapper');
            let icon = dropdown.querySelector('button > span:last-child');
            if(w) w.classList.add('opacity-0', 'invisible', '-translate-y-2', 'pointer-events-none');
            if(icon) icon.classList.remove('rotate-180');
        }

        if(input && input.onchange) {
            input.onchange(new Event('change'));
        } else if (input) {
            input.dispatchEvent(new Event('change'));
        }
    },

    render: function(id) {
        let wrapper = document.getElementById(id + '-wrapper');
        if(!wrapper) return;
        
        let state = this.instances[id];
        const firstDay = new Date(state.currentYear, state.currentMonth, 1).getDay();
        const daysInMonth = new Date(state.currentYear, state.currentMonth + 1, 0).getDate();
        const daysInPrevMonth = new Date(state.currentYear, state.currentMonth, 0).getDate();
        
        let html = `
        <div class="flex items-center justify-between mb-4 px-2">
            <button type="button" onclick="HospitalCalendar.changeMonth('${id}', -1, event)" class="text-on-surface-variant hover:text-black hover:bg-surface-container-low rounded-full p-1 transition-colors active:scale-95"><span class="material-symbols-outlined">chevron_left</span></button>
            <p class="text-xs font-bold text-black uppercase tracking-widest">${this.monthsPT[state.currentMonth]} ${state.currentYear}</p>
            <button type="button" onclick="HospitalCalendar.changeMonth('${id}', 1, event)" class="text-on-surface-variant hover:text-black hover:bg-surface-container-low rounded-full p-1 transition-colors active:scale-95"><span class="material-symbols-outlined">chevron_right</span></button>
        </div>
        <div class="bg-surface-container-low rounded-[24px] p-5">
            <div class="grid grid-cols-7 gap-y-3 text-center text-xs font-semibold w-full">
                <span class="text-on-surface-variant/50">D</span><span>S</span><span>T</span><span>Q</span><span>Q</span><span>S</span><span class="text-on-surface-variant/50">S</span>
        `;
        
        for(let i = 0; i < firstDay; i++) {
            let d = daysInPrevMonth - firstDay + i + 1;
            html += `<span class="text-on-surface-variant/30 flex items-center justify-center">${d}</span>`;
        }
        
        for(let i = 1; i <= daysInMonth; i++) {
            let thisDate = new Date(state.currentYear, state.currentMonth, i);
            thisDate.setHours(0,0,0,0);
            
            let mStr = String(state.currentMonth+1).padStart(2,'0');
            let dStr = String(i).padStart(2,'0');
            let dateStr = `${state.currentYear}-${mStr}-${dStr}`;
            let formattedDate = `${dStr}/${mStr}/${state.currentYear}`;
            
            let isSelected = (dateStr === state.selectedDate);
            
            let isDisabled = false;
            if(state.minDate && thisDate.getTime() < state.minDate) isDisabled = true;
            
            if(isDisabled) {
                html += `<button type="button" disabled class="hover:bg-white rounded-[12px] w-8 h-8 flex items-center justify-center mx-auto transition-all disabled:opacity-30">${i}</button>`;
            } else if(isSelected) {
                html += `<button type="button" onclick="HospitalCalendar.selectDate('${id}', '${dateStr}', '${formattedDate}', event)" class="bg-black text-white rounded-[12px] w-8 h-8 flex items-center justify-center mx-auto shadow-md transition-all hover:scale-[1.1]">${i}</button>`;
            } else {
                html += `<button type="button" onclick="HospitalCalendar.selectDate('${id}', '${dateStr}', '${formattedDate}', event)" class="hover:bg-white rounded-[12px] w-8 h-8 flex flex-col items-center justify-center mx-auto transition-all hover:scale-[1.1] hover:shadow-sm text-black relative"><span class="z-10">${i}</span></button>`;
            }
        }
        
        let totalCells = firstDay + daysInMonth;
        let nextDays = 0;
        while(totalCells % 7 !== 0) {
            nextDays++;
            html += `<span class="text-on-surface-variant/30 flex items-center justify-center">${nextDays}</span>`;
            totalCells++;
        }
        
        html += `
            </div>
        </div>`;
        wrapper.innerHTML = html;
    },

    toggleDropdown: function(id, event) {
        if(event) { event.preventDefault(); event.stopPropagation(); }
        
        document.querySelectorAll('.custom-calendar-dropdown').forEach(el => {
            if(el.id !== id + '-dropdown') {
                el.classList.remove('active');
                let w = el.querySelector('.custom-cal-wrapper');
                let icon = el.querySelector('button > span:last-child');
                if(w) w.classList.add('opacity-0', 'invisible', '-translate-y-2', 'pointer-events-none');
                if(icon) icon.classList.remove('rotate-180');
            }
        });
        
        let dropdown = document.getElementById(id + '-dropdown');
        if(dropdown) {
            let isActive = dropdown.classList.toggle('active');
            let wrapper = document.getElementById(id + '-wrapper');
            let icon = dropdown.querySelector('button > span:last-child');
            if(wrapper) {
                if(isActive) {
                    wrapper.classList.remove('opacity-0', 'invisible', '-translate-y-2', 'pointer-events-none');
                } else {
                    wrapper.classList.add('opacity-0', 'invisible', '-translate-y-2', 'pointer-events-none');
                }
            }
            if(icon) {
                if(isActive) icon.classList.add('rotate-180');
                else icon.classList.remove('rotate-180');
            }
        }
    }
};

document.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-calendar-dropdown')) {
        document.querySelectorAll('.custom-calendar-dropdown.active').forEach(function(el) {
            el.classList.remove('active');
            let w = el.querySelector('.custom-cal-wrapper');
            let icon = el.querySelector('button > span:last-child');
            if(w) w.classList.add('opacity-0', 'invisible', '-translate-y-2', 'pointer-events-none');
            if(icon) icon.classList.remove('rotate-180');
        });
    }
});
