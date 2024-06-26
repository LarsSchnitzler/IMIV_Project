//-------------------Functions-------------------
function getStory(dsN){
  if (dsN === 'temperature') {
      return "Temperature is the average kinetic energy of the molecules in an environment. It influences the rate of chemical reactions, and governs physical properties of materials like density, solubility, and phase transitions.";
  } else if (dsN === 'humidity') {
      return "Humidity is the amount of water vapor in the air. It's usually expressed as a percentage. High humidity can make hot temperatures feel hotter, while low humidity can make cold temperatures feel colder.";
  } else if (dsN === 'precipitation') {
      return "Precipitation refers to any water that falls from the sky, including rain, snow, sleet, and hail. The release of water from the atmosphere in the form of rain, sleet, snow, or hail. It's a crucial part of the water cycle, and affects soil erosion, water supply, and the life cycles of plants and animals.";
  } else if (dsN === 'pressure') {
      return "Pressure Also known as atmospheric pressure, this is the force exerted by the weight of the air above a given point. Hot air is less dense and therefore exerts less Pressure.";
  } else if (dsN === 'windspeed') {
      return "Wind speed can affect outdoor activities and can cause damage if it's too high.";
  } else if (dsN === 'visibility') {
      return "The maximum distance at which objects can be clearly discerned. It's affected by a lot of factors like fog, dust, and air pollution, and is important for activities like driving and aviation.";
  } else if (dsN === 'cloudcover') {
      return "The fraction of the sky obscured by clouds. It affects the albedo of the Earth and plays a role in the planet's energy balance.";
  } else if (dsN === 'solarenergy') {
      return "Closely related to that should be the measure of solar energy. Its a measurement of Power per area. Interestingly, you can sometimes observe temperature going down while solar energy is going up. This could be due to Cloud Cover, Athmospheric Conditions, Seasonal Change in the angle of the sun, or most likely Heat Storage. The Earth's surface can store heat and release it over time. So, a decrease in solar energy might not immediately lead to a decrease in temperature, especially if the previous days were sunny and warm.";
  } else {
      return "No information available for this dataset.";
  }
}

function reduceLabels(labels, ts) {
  if (ts === 0) {
    return labels.map((label, index) => index % 4 === 0 ? label : '');
  }
  else if (ts === 6) {
    return labels.map((label, index) => index % 16 === 0 ? label : '');
  }
  else if (ts === 13) {
    return labels.map((label, index) => index % 32 === 0 ? label : '');
  }
}

//takes all time values in unix timestamp format besides length which is an integer. returns an array of integers between 0 and len
function mapDtArray(arr, max, min, len) {
  const timespan = max - min;
  return arr.map(value => (value - min) / timespan * len);
}

//canvasContainer should be id of container, data should be object with date_time as keys, titleString should be string
function displayDataset(unit, dta, datasetName, sr, container, stry, ts){ 
  //---------create box----------
  const ct = document.createElement('div');
  ct.className = 'canvas-container';

  const title = document.createElement('h3');
  title.textContent = datasetName + '[' + unit + '] over time';
  ct.appendChild(title);

  const canvas = document.createElement('canvas');
  ct.appendChild(canvas);

  const text = document.createElement('p');
  text.textContent = stry;
  ct.appendChild(text);

  const ctx = canvas.getContext('2d');

  //append box to container
  container.appendChild(ct);
  
  //----------create chart----------

  //get labels and data from argument 'dta'
  const originalLabels = Object.keys(dta);

  const labels = reduceLabels(originalLabels, ts);

  const data = Object.values(dta);

  //create an object for the annotations (sunset from array 'sr')
  let annots = [];

  for (let i = 0; i < sr.length; i++) {
    annots.push({
      drawTime: 'beforeDatasetsDraw',
      type: 'line',
      scaleID: 'x',
      value: sr[i],
      borderColor: 'rgb(255, 255, 0)',
      borderWidth: 3,
    });
  }

  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          data: data,
          borderColor: 'rgb(25, 50, 100)',
          borderWidth: 2,
          tension: 0.25,
          pointRadius: 0
        }
      ]
    },
    options: {
      plugins: {
        legend: { display: false },
        annotation: { annotations: annots}
      },
      scales: {
        x: { ticks: { autoSkip: false, maxRotation: 90, minRotation: 90  } },
        y: { beginAtZero: true, title: { display: true, text: unit } }
      }
    }
  });
}

//-------------------Async Functions-------------------
async function fetchData(d,dback) {
  //check if d and dback is a valid date of format yyyy-mm-dd
  console.log('fetchData called with d:', d, 'dback:', dback);
  const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
  if (!dateRegex.test(d) || isNaN(dback)) {
    console.log('fetchData recieved invalid date format (meaning not yyyy-mm-dd) or dback is not a number');
    return false;
  }

  try {
    const params = new URLSearchParams();
    params.append('day', d);
    params.append('daysBack', dback);

    const response = await fetch('./db_to_frontend.php?' + params.toString());
    const data = await response.json();
    console.log('Data fetched:', data);
    return data;
  } catch (error) {
    console.log('Error:', error);
    return null;
  }
}

async function main(timespan) {
  let data;
  const chartContainer = document.getElementById('chart-container');
  chartContainer.innerHTML = '';

  if (timespan === 0){
    data = await fetchData(new Date().toISOString().slice(0,10), 0);
  }
  else if (timespan === 6){
    data = await fetchData(new Date().toISOString().slice(0,10), 6);
  }
  else if (timespan === 13){
    data = await fetchData(new Date().toISOString().slice(0,10), 13);
  }
  else {
    console.log('main recieved invalid timespan');
    return false;
  }

  //seperate dataset 'sunrise' from rest of data
  const sunrise = data['data']['sunrise'];
  delete data['data']['sunrise'];
  //concatenate sunrise datetime values
  const sunriseDatetimes = [];
  for (let [date, time] of Object.entries(sunrise)) {
    let datetimeString = date + ' ' + time;
    sunriseDatetimes.push(datetimeString);
  }

  //get array of datasetNames
  const keys = Object.keys(data['data']);

  for (let i = 0; i < keys.length; i++) {
    //get unit for datasetName
    const ut = data['units'][keys[i]];

    //get dataset for datasetName
    const ds = data['data'][keys[i]];

    //get datasetName
    const dsName = keys[i];

    //get story
    const story = getStory(dsName);

    //transform sunriseDatetimes to value between 0 and (data['data'][keys[i]].length)
    //first transform sunriseDatetimes to unix timestamp
    sunriseDatetimes.forEach((datetime, index) => {
      sunriseDatetimes[index] = new Date(datetime).getTime();
    });
    //second: get max time value of dataset and min time value of dataset
    const kys = Object.keys(ds);
    const firstKey = kys[0];
    const lastKey = kys[kys.length-1];
    //transform them into unix timestamp
    const max = new Date(lastKey).getTime();
    const min = new Date(firstKey).getTime();
    //third: get length of dataset
    const len = Object.keys(ds).length;
    //Now we can map the sunriseDatetimes to the dataset
    const srXKoords = mapDtArray(sunriseDatetimes, max, min, len);

    displayDataset(ut, ds, dsName, srXKoords, chartContainer, story, timespan);
  }
  return true;
}

//-------------------Main Code/Eventlisteners-------------------
document.addEventListener('DOMContentLoaded', () => {
  console.log('DOM fully loaded and parsed');
  let btnToday = document.getElementById('btnToday');
  let btn1Week = document.getElementById('btn1w');
  let btn2Week = document.getElementById('btn2w');

  main(0);

  btnToday.addEventListener('click', () => {
    main(0);
  });
  btn1Week.addEventListener('click', () => {
    main(6);
  });
  btn2Week.addEventListener('click', () => {
    main(13);
  });
});