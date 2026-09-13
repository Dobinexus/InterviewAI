const API = "api.php";
const state = { user:null, currentPage:"dashboard", interview:null, questions:[] };

const $ = id => document.getElementById(id);
function toast(msg){ const t=$("toast"); t.textContent=msg; t.classList.add("show"); setTimeout(()=>t.classList.remove("show"),2800); }
async function api(action, data={}){
  const options={method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({...data,action})};
  const r=await fetch(API,options);
  const j=await r.json().catch(()=>({success:false,message:"Invalid server response."}));
  if(!r.ok || !j.success) throw new Error(j.message||"Request failed.");
  return j;
}
function showLogin(){ $("loginForm").classList.remove("hidden"); $("registerForm").classList.add("hidden"); }
function showRegister(){ $("loginForm").classList.add("hidden"); $("registerForm").classList.remove("hidden"); }
function showApp(){ $("authView").classList.add("hidden"); $("appView").classList.remove("hidden"); $("userName").textContent=state.user.name; renderPage("dashboard"); }
function initials(name){return name.split(" ").map(x=>x[0]).slice(0,2).join("").toUpperCase();}
function navActive(page){document.querySelectorAll(".nav-item[data-page]").forEach(b=>b.classList.toggle("active",b.dataset.page===page));}
function renderPage(page){
  state.currentPage=page; navActive(page);
  const titles={dashboard:"Dashboard",newInterview:"Create New Interview",questions:"AI Question Guide",interviews:"Interview Sessions",insights:"Requirement Insights",reports:"Interview Reports"};
  $("pageTitle").textContent=titles[page]||"Dashboard";
  const c=$("pageContent");
  if(page==="dashboard") return renderDashboard(c);
  if(page==="newInterview") return renderNewInterview(c);
  if(page==="questions") return renderQuestions(c);
  if(page==="interviews") return renderInterviews(c);
  if(page==="insights") return renderInsights(c);
  if(page==="reports") return renderReports(c);
}
function renderDashboard(c){
 c.innerHTML=`<div class="page-grid">
   <div class="stat"><div class="icon">＋</div><h3>Start a discovery</h3><p>Create an interview plan for a system.</p><button class="secondary-btn" onclick="renderPage('newInterview')">New interview →</button></div>
   <div class="stat"><div class="icon">?</div><h3>Generate questions</h3><p>Build role-specific prompts with AI logic.</p><button class="secondary-btn" onclick="renderPage('questions')">View guide →</button></div>
   <div class="stat"><div class="icon">✧</div><h3>Capture insights</h3><p>Turn responses into recurring requirements.</p><button class="secondary-btn" onclick="renderPage('insights')">Open insights →</button></div>
 </div>
 <div class="panel wide-panel"><h3>InterviewAI workflow</h3><div class="quick-grid">
  <div class="quick"><strong>01 · Prepare</strong><span>Define the system, interviewee role, and goals.</span></div>
  <div class="quick"><strong>02 · Ask</strong><span>Use structured questions and adaptive follow-ups.</span></div>
  <div class="quick"><strong>03 · Capture</strong><span>Record notes and organize evidence during the session.</span></div>
  <div class="quick"><strong>04 · Discover</strong><span>Summarize patterns and convert them into requirements.</span></div>
 </div></div>`;
}
function renderNewInterview(c){
 c.innerHTML=`<div class="panel"><h3>Interview setup</h3><p class="muted">Tell InterviewAI what you are investigating. The prototype will prepare a focused guide.</p>
 <form id="newInterviewForm"><div class="form-grid">
  <div class="field"><label>System type</label><input id="systemType" required placeholder="e.g. Campus Clinic Management System"></div>
  <div class="field"><label>Interviewee role</label><select id="role"><option>Student</option><option>Staff</option><option>Administrator</option><option>Customer</option><option>Manager</option><option>Other</option></select></div>
  <div class="field full"><label>Interview title (optional)</label><input id="interviewTitle" placeholder="e.g. Clinic workflow discovery"></div>
  <div class="field full"><label>Discovery focus</label><textarea id="focus" placeholder="What do you want to understand? Include workflows, pain points, limitations, or desired features."></textarea></div>
 </div><div class="actions"><button class="primary-btn" type="submit">Prepare interview guide →</button></div></form></div>`;
 $("newInterviewForm").onsubmit=async e=>{e.preventDefault(); try{const res=await api("create_interview",{system_type:$("systemType").value,role:$("role").value,title:$("interviewTitle").value,focus:$("focus").value});state.interview=res.interview;state.questions=res.questions;toast("Interview guide prepared.");renderPage("questions")}catch(err){toast(err.message)}};
}
function renderQuestions(c){
 if(!state.questions.length){c.innerHTML=`<div class="panel empty">No question guide yet.<br><br><button class="primary-btn" onclick="renderPage('newInterview')">Create an interview</button></div>`;return}
 c.innerHTML=`<div class="panel"><div class="row-between"><div><h3>AI-prepared question guide</h3><p class="muted">Questions are organized around the requirements discovery objectives.</p></div><span class="tag">${state.questions.length} QUESTIONS</span></div>
 <div class="question-list">${state.questions.map((q,i)=>`<div class="question"><div class="qnum">${i+1}</div><div><strong>${q.category}</strong><p>${q.question}</p></div><span class="tag">${q.priority}</span></div>`).join("")}</div>
 <div class="actions"><button class="secondary-btn" onclick="renderPage('newInterview')">Regenerate</button><button class="primary-btn" onclick="renderPage('interviews')">Start interview →</button></div></div>`;
}
function renderInterviews(c){
 c.innerHTML=`<div class="interview-room"><div class="panel transcript"><div class="row-between"><h3>Interview room</h3><button class="secondary-btn" onclick="finishInterview()">End interview</button></div>
 ${state.interview?`<p class="muted"><strong>${state.interview.title||"Discovery Interview"}</strong> · ${state.interview.role}</p>`:""}
 <div class="message"><strong>INTERVIEWER</strong><p>What part of the current process causes the most difficulty?</p></div>
 <div class="message"><strong>INTERVIEWEE</strong><p>Finding the right record takes too long, especially when there are many entries.</p></div>
 <div class="message"><strong>INTERVIEWER</strong><p>What do you currently do when that happens?</p></div>
 <div class="message"><strong>INTERVIEWEE</strong><p>We search manually and sometimes ask another staff member for help.</p></div>
 <div class="recording"><span>● Notes capture active</span><strong>00:05:23</strong></div></div>
 <div class="panel"><h3>AI listening</h3><div class="finding"><strong>Key point</strong><br>Record retrieval is slow.</div><div class="finding"><strong>Possible requirement</strong><br>Provide faster record search and filtering.</div><div class="finding"><strong>Suggested follow-up</strong><br>What information do you usually search by?</div><button class="primary-btn wide" onclick="finishInterview()">Complete session</button></div></div>`;
}
function finishInterview(){toast("Interview completed. Insights are ready.");renderPage("insights")}
function renderInsights(c){
 c.innerHTML=`<div class="report-grid"><div class="panel"><h3>Requirement insights</h3><p class="muted">Patterns detected from the captured interview.</p>
 <div class="finding"><strong>01 · Search efficiency</strong><br>Users need faster ways to locate records and information.</div>
 <div class="finding"><strong>02 · Process visibility</strong><br>Users want clearer status information instead of manual checking.</div>
 <div class="finding"><strong>03 · Notifications</strong><br>Automated reminders could reduce missed actions.</div>
 </div><div class="panel"><h3>Theme strength</h3><strong>Workflow friction</strong><div class="bar"><i style="width:88%"></i></div><strong>Search & retrieval</strong><div class="bar"><i style="width:76%"></i></div><strong>Automation</strong><div class="bar"><i style="width:61%"></i></div></div></div>`;
}
function renderReports(c){
 c.innerHTML=`<div class="panel"><div class="row-between"><div><h3>Interview report</h3><p class="muted">A concise post-interview summary for requirements analysis.</p></div><button class="primary-btn" onclick="window.print()">Print report</button></div>
 <div class="report-grid"><div><h3>Summary</h3><p style="line-height:1.7;font-size:13px">The interview identified workflow delays, manual record searching, and a need for better visibility. These findings can guide functional and usability requirements for the proposed information system.</p></div><div><h3>Recommendations</h3><div class="finding">✓ Add advanced search and filters</div><div class="finding">✓ Add clear process/status indicators</div><div class="finding">✓ Add configurable reminders</div></div></div></div>`;
}
async function loadInterviews(){try{const r=await api("list_interviews"); if(r.interviews?.length){} }catch(e){}}

$("showRegister").onclick=showRegister; $("showLogin").onclick=showLogin;
document.querySelectorAll(".eye").forEach(b=>b.onclick=()=>{const x=$(b.dataset.target);x.type=x.type==="password"?"text":"password"});
$("forgotBtn").onclick=()=>toast("For the prototype, contact the system administrator to reset an interviewer password.");
$("registerForm").onsubmit=async e=>{
 e.preventDefault();
 const name=$("regName").value.trim(),email=$("regEmail").value.trim(),p=$("regPassword").value,cp=$("regConfirm").value;
 if(p!==cp){toast("Passwords do not match.");return}
 try{const r=await api("register",{name,email,password:p});state.user=r.user; $("userName").textContent=name; showApp();toast("Account created successfully.");}
 catch(err){toast(err.message)}
};
$("loginForm").onsubmit=async e=>{
 e.preventDefault();
 try{const r=await api("login",{email:$("loginEmail").value.trim(),password:$("loginPassword").value});state.user=r.user;showApp();toast("Welcome back.");}
 catch(err){toast(err.message)}
};
document.querySelectorAll(".nav-item[data-page]").forEach(b=>b.onclick=()=>renderPage(b.dataset.page));
$("logoutBtn").onclick=()=>{state.user=null;$("appView").classList.add("hidden");$("authView").classList.remove("hidden");showLogin();toast("Signed out.");};
