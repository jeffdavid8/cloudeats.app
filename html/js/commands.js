mb.commands = {
  'say': function(data)
  {
    speak(data);
  },
  'play': function(data)
  {
    play(data);
  },
  'notify': function(data)
  {
    notify(data);
  },
  'process': function(data)
  {
    // Sample Commands
    //
    // play audio/star trek sounds/computerbeep_16.mp3
    // play audio/star trek sounds/youarenotauthorisedtoaccessthisfacility_clean.mp3
    // process {sampleData:true, extension:'sampleDataProcessor3589d'}

    data = data.replace(/'/g, '"') // Replace single quotes with double quotes
          .replace(/\\"/g, '"') // Replace escaped double quotes with regular double quotes
          .replace(/([a-zA-Z0-9_-]+):/g, '"$1":'); // Add quotes around keys

    try {
      JSON.parse(data);
    } catch (e) {
      return false;
    }

    data = JSON.parse(data)
    mb.process(data);
  }
} 
