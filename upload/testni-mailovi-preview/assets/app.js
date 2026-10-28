(function(){
  'use strict';
  const scenarios = window.MAIL_PREVIEW_SCENARIOS || [];
  const state = {type:'all', channel:'all', code:'all', search:'', selected:null};
  const $ = (id) => document.getElementById(id);
  const list = $('scenario-list');
  const channelFilter = $('channel-filter');
  const codeFilter = $('code-filter');

  function uniqueSorted(values){return Array.from(new Set(values)).sort((a,b)=>a.localeCompare(b,'hr'));}
  function option(value,label){const el=document.createElement('option');el.value=value;el.textContent=label;return el;}

  uniqueSorted(scenarios.map(s=>s.channel)).forEach(label=>channelFilter.appendChild(option(label,label)));
  const codes = uniqueSorted(scenarios.map(s=>s.code));
  codes.forEach(code=>{const first=scenarios.find(s=>s.code===code);codeFilter.appendChild(option(code,code+' · '+first.to));});

  function filtered(){
    const needle=state.search.trim().toLocaleLowerCase('hr');
    return scenarios.filter(s=>{
      if(state.type!=='all'&&s.type!==state.type)return false;
      if(state.channel!=='all'&&s.channel!==state.channel)return false;
      if(state.code!=='all'&&s.code!==state.code)return false;
      if(!needle)return true;
      return [s.code,s.from,s.to,s.channel,s.subject,s.customer.customer,s.payment,s.fulfilment].join(' ').toLocaleLowerCase('hr').includes(needle);
    });
  }

  function renderList(){
    const rows=filtered();
    $('result-count').textContent=rows.length+' / '+scenarios.length;
    list.replaceChildren();
    if(!rows.length){const empty=document.createElement('div');empty.className='empty-state';empty.textContent='Nema scenarija za odabrane filtre.';list.appendChild(empty);return;}
    if(!state.selected||!rows.some(s=>s.id===state.selected)){state.selected=rows[0].id;}
    rows.forEach(s=>{
      const button=document.createElement('button');button.type='button';button.className='scenario-card'+(s.id===state.selected?' active':'');button.dataset.id=s.id;
      const top=document.createElement('div');top.className='top';
      const code=document.createElement('span');code.className='code';code.textContent=s.code;
      const type=document.createElement('span');type.className='type';type.textContent=s.type==='status'?'Statusni':'Vremenski';
      if(s.warnings.length){const dot=document.createElement('i');dot.className='warn-dot';dot.title='Scenarij ima napomenu za potvrdu';type.appendChild(dot);}
      top.append(code,type);
      const strong=document.createElement('strong');strong.textContent=s.channelShort;
      const small=document.createElement('small');small.textContent=s.from+' → '+s.to;
      button.append(top,strong,small);
      button.addEventListener('click',()=>{state.selected=s.id;renderList();renderSelected();});
      list.appendChild(button);
    });
    renderSelected();
  }

  function setText(id,value){$(id).textContent=value||'—';}
  function renderSelected(){
    const s=scenarios.find(item=>item.id===state.selected)||filtered()[0];
    if(!s)return;
    state.selected=s.id;
    setText('scenario-code',s.code+' · '+(s.type==='status'?'statusni mail':'vremenski mail'));
    setText('scenario-title',s.channelShort);
    setText('trigger',s.from+' → '+s.to);
    setText('channel',s.channel);
    setText('buyer',s.customer.customer+(s.customer.company?' · '+s.customer.company:''));
    setText('subject',s.subject);
    setText('billing-street',s.customer.billing.street);
    setText('billing-number',s.customer.billing.number);
    setText('billing-line',s.customer.billing.line);
    setText('shipping-line',s.customer.shipping.line+', '+s.customer.shipping.postcode+' '+s.customer.shipping.city);
    setText('contact',s.customer.phone+' · '+s.customer.email);
    setText('profile',s.profileLabel);
    const timing=$('timing-note');
    if(s.timing){timing.hidden=false;timing.textContent=(s.workingDays?'Radni dani / rok: ':'Vrijeme: ')+s.timing;}else{timing.hidden=true;timing.textContent='';}
    const warning=$('warning-list');
    const notes=[...(s.warnings||[])];if(s.sourceNote)notes.push(s.sourceNote);
    if(notes.length){warning.hidden=false;warning.innerHTML='<ul>'+notes.map(n=>'<li>'+escapeHtml(n)+'</li>').join('')+'</ul>';}else{warning.hidden=true;warning.textContent='';}
    $('mail-frame').src=s.file;
    $('open-mail').href=s.file;
    history.replaceState(null,'','#'+encodeURIComponent(s.id));
  }

  function escapeHtml(value){return String(value).replace(/[&<>"']/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));}

  document.querySelectorAll('.type-filter').forEach(button=>button.addEventListener('click',()=>{
    document.querySelectorAll('.type-filter').forEach(item=>item.classList.toggle('active',item===button));
    state.type=button.dataset.type;renderList();
  }));
  channelFilter.addEventListener('change',()=>{state.channel=channelFilter.value;renderList();});
  codeFilter.addEventListener('change',()=>{state.code=codeFilter.value;renderList();});
  $('search').addEventListener('input',event=>{state.search=event.target.value;renderList();});
  document.querySelectorAll('.viewport').forEach(button=>button.addEventListener('click',()=>{
    document.querySelectorAll('.viewport').forEach(item=>item.classList.toggle('active',item===button));
    const shell=$('preview-frame-shell');shell.classList.toggle('mobile',button.dataset.viewport==='mobile');shell.classList.toggle('desktop',button.dataset.viewport!=='mobile');
  }));

  const hash=decodeURIComponent(location.hash.slice(1));
  if(hash&&scenarios.some(s=>s.id===hash))state.selected=hash;
  renderList();
})();
